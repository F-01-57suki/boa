<?php
header('X-FRAME-OPTIONS: SAMEORIGIN');
//DB情報
$dbname='mysql:host=localhost;dbname=oneline;charset=utf8';//mysql1.php.xdomain.ne.jp
$id='root';//crimsonscar_root
$pw='';//Ha020714
//DB接続
try{
  $pdo=new pdo($dbname,$id,$pw,array(PDO::ATTR_EMULATE_PREPARES=>false));
}
catch(PDOException $e){
  die('DB接続に失敗'.$e->getMessage());//後で消す
}

//POSTなら保存処理
$errors=array();
if($_SERVER['REQUEST_METHOD']==='POST'):
  //たいとる
  $title=null;
  if(isset($_POST['title'])):
    if(strlen($_POST['title'])>=1 and strlen($_POST['title'])<=80)://全角80まで、バイト数240まで
      $title=htmlspecialchars($POST['title'],ENT_QUOTES,'UTF-8');
    else:
      $errors['title']="たいとるは1～80文字の間で入力して下さい";
    endif;
  else:
    $errors['title']="たいとるを入力して下さい";
  endif;
    //こめんと
    $comment=null;
    if(isset($_POST['comment'])):
      if(strlen($_POST['comment'])>=1 and strlen($_POST['comment'])<=420)://全角140まで、バイト数420まで
        $comment=htmlspecialchars($POST['comment'],ENT_QUOTES);
      else:
        $errors['comment']="つぶやきは1～140文字の間で入力して下さい";
      endif;
    else:
      $errors['comment']="つぶやきを入力して下さい";
    endif;
  //なまえ
  $screen_name=null;
  if(isset($_POST['screen_name'])):
    if(strlen($_POST['screen_name'])>=1 and strlen($_POST['screen_name'])<=60):
      $screen_name=htmlspecialchars($POST['screen_name'],ENT_QUOTES,'UTF-8');
    else:
      $errors['screen_name']="なまえは1～20文字の間で入力して下さい";
    endif;
  else:
    $errors['screen_name']="なまえを入力して下さい";
  endif;

  //エラー確認
  if(count($errors)===0):
    $created_at=date('Y-m-d H:i:s');
    $sql="INSERT INTO `post`(`title`,`comment`,`screen_name`,`created_at`) VALUES(:title,:comment,:screen_name,:created_at)";
    $stmt=$pdo->prepare($sql);
    $stmt->bindParam(':title',$_POST['title']);
    $stmt->bindParam(':comment',$_POST['comment']);
    $stmt->bindParam(':screen_name',$_POST['screen_name']);
    $stmt->bindParam(':created_at',$created_at);
    $stmt->execute();
    $stmt=null;
    $pdo=null;//PDOおわり
    header('Location: http://' .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  endif;
endif;

//GETならページャー処理
//ダメだったらセッション？なやみ
$offset=0;
$rowcount=10;
if($_SERVER['REQUEST_METHOD']==='GET'):
  $offset+=($_GET['offset']);
  $rowcount+=($_GET['rowcount']);
endif;
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <title>チラ裏</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="wrapper">
      <header>
        <h1>チラシの裏</h1>
        <nav>
          <ul>
            <li id="write">書く</li>
          </ul>
        </nav>
      </header>
      <main>
        <?php if(count($errors)!==0): ?>
        <p>エラーを確認してください。</p>
        <ul class="error_list">
          <?php foreach($errors as $value): ?>
          <li><?php echo htmlspecialchars($value,ENT_QUOTES,'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <div id="form">
          <form action="index.php" method="post">
            <dl>
              <dt>たいとる：</dt>
              <dd><input type="text" name="title"></dd>
              <dt>つぶやき：</dt>
              <dd><textarea name="comment" cols="30" rows="10"></textarea></dd>
              <dt>なまえ：</dt>
              <dd><input type="text" name="screen_name"></dd>
              <dd><input type="submit" name="submit" value="送信"></dd>
            </dl>
          </form>
        </div>
        <?php
        //一覧の取得
        $sql='SELECT * FROM `post` ORDER BY `created_at` DESC LIMIT :offset, :rowcount';
        $stmt=$pdo->prepare($sql);
        $stmt->bindParam(':offset',$offset);
        $stmt->bindParam(':rowcount',$rowcount);
        $stmt->execute();
//        $result=$stmt->fetch(PDO::FETCH_ASSOC);
//        if($result):
        while($post=$stmt->fetch(PDO::FETCH_ASSOC)):
          ?>
          <h2><?php echo htmlspecialchars($post['title'],ENT_QUOTES,'UTF-8'); ?></h2>
          <table>
            <tr>
              <th>なまえ</th>
              <td><?php echo htmlspecialchars($post['screen_name'],ENT_QUOTES,'UTF-8'); ?></td>
            </tr>
            <tr>
            <th>なかみ</th>
              <td><?php echo htmlspecialchars($post['comment'],ENT_QUOTES,'UTF-8'); ?></td>
            </tr>
            <tr>
              <td colspan="2"><?php echo htmlspecialchars($post['created_at'],ENT_QUOTES,'UTF-8'); ?></td>
            </tr>
          </table>
          <?php
        endwhile;
          $stmt=null;
          $pdo = null;
        ?>
        <div id="pager">
          <?php if($offset>=0): ?>
          <a href="index.php?offset=-10&rowcount=-10<?php echo $rowcount; ?>">もどる</a>
          <?php endif; ?>
          ／
          <?php if($rowcount<99): ?>
          <a href="index.php?offset=10&rowcount=10<?php echo $rowcount; ?>">もどる</a>
          <?php endif; ?>
        </div>
      </main>
      <footer>
        <p>copyright &copy; <?php echo date('Y'); ?> Miyashita.</p>
      </footer>
    </div>
  </body>
</html>