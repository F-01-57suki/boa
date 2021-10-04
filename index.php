<?php
header('X-FRAME-OPTIONS: SAMEORIGIN');
//DB情報
$dbname = 'mysql:host=localhost;dbname=backofads;charset=utf8';//mysql1.php.xdomain.ne.jp
$id = 'root';//crimsonscar_root
$pw = '';//Ha020714
//DB接続
try{
  $pdo = new pdo($dbname,$id,$pw,array(PDO::ATTR_EMULATE_PREPARES => false));
}
catch(PDOException $e){
  die('DB接続に失敗'.$e->getMessage());//後で消す
}

//POSTなら保存処理
$errors = array();
if($_SERVER['REQUEST_METHOD'] === 'POST'):
  //タイトルのチェック
  $title = null;
  if(isset($_POST['title'])):
    if(strlen($_POST['title']) >= 1 and strlen($_POST['title']) <= 60)://全角20まで、バイト数60まで
      $title = htmlspecialchars($_POST['title'],ENT_QUOTES,'UTF-8');
    else:
      $errors['title'] = "タイトルは全角20文字以内";
    endif;
  else:
    $errors['title'] = "タイトルは必須（無題でもイイヨ）";
  endif;
  //コメントのチェック
  $comment = null;
  if(isset($_POST['comment'])):
    if(strlen($_POST['comment']) >= 1 and strlen($_POST['comment']) <= 600)://全角200まで、バイト数600まで
      $comment = nl2br(htmlspecialchars($_POST['comment'],ENT_QUOTES));
    else:
      $errors['comment'] = "内容は全角200文字以内";
    endif;
  else:
    $errors['comment'] = "内容は必須";
  endif;
  //名前のチェック
  $screen_name = null;
  if(isset($_POST['screen_name'])):
    if(strlen($_POST['screen_name']) >= 1 and strlen($_POST['screen_name']) <= 60):
      $screen_name = htmlspecialchars($_POST['screen_name'],ENT_QUOTES,'UTF-8');
    else:
      $errors['screen_name'] = "名前は全角20文字以内";
    endif;
  else:
    $errors['screen_name'] = "名前は必須（匿名でもイイヨ）";
  endif;

  //画像
  if($_FILES['img']['size'] !== 0):
    $img_at = date('ymdHis');
    $imgError = $_FILES['img']['error'];
    $imgSize = $_FILES['img']['size'];
    $imgType = $_FILES['img']['type'];
    $imgName = $_FILES['img']['name'];
    $imgCheck = "./img/".$img_at.$imgName;
    //エラーチェック
    if($imgError != 0):
      $errors['imgError'] = "画像アップロードに失敗";
    endif;
    //種類・サイズチェック
    if($imgSize >= 500000):
      $errors['imgSize'] = "画像サイズが大きい";
    endif;
    if($imgType != "image/jpeg" && $imgType != "image/png" && $imgType != "image/gif"):
      $errors['imgType'] = "画像はjpeg/png/gifのみ可";
    endif;
    //ファイル名チェック
    if(file_exists($imgCheck) == false):
      $img = $img_at.$imgName;
      move_uploaded_file($_FILES['img']['tmp_name'], "./img/$img");
    else:
      $errors['imgCheck'] = "画像アップロードに失敗";
    endif;
  else:
    $img = "nothing.jpg";
  endif;

  //エラー確認
  if(count($errors) === 0):
    $created_at = date('Y-m-d H:i:s');
    $sql = "INSERT INTO `post`(`title`,`comment`,`screen_name`,`img`,`created_at`) VALUES(:title,:comment,:screen_name,:img,:created_at)";
    $stmt = $pdo->prepare($sql);
    $stmt -> bindParam(':title',$_POST['title']);
    $stmt -> bindParam(':comment',$_POST['comment']);
    $stmt -> bindParam(':screen_name',$_POST['screen_name']);
    $stmt -> bindParam(':img',$img);
    $stmt -> bindParam(':created_at',$created_at);
    $stmt -> execute();
    $stmt = null;
    $pdo = null;
    header('Location: http://' .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  endif;
endif;

//ページャー処理
$sql = "SELECT COUNT(*) FROM `post`";
$stmt = $pdo -> prepare($sql);
$stmt -> execute();
$count = $stmt -> fetchColumn();//レコード総数
$perPage = 5; //ページあたりのデータ件数
$totalPage = ceil($count / $perPage); // 最大ページ数（ceilで端数切上）
//総数÷ページ5件
if(isset($_GET['page'])):
$page = (int) $_GET['page'];//現在ページ（GETでページ数を投げて受ける
else:
$page = 1;//初期１
endif;

$prev = max($page - 1,1);//戻るで投げるGET数
$next = min($page + 1,$totalPage);//進むで投げるGET数

$offset = 5 * ($page - 1);

?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <title>チラ裏</title>
    <link href="style.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet">
  </head>
  <body>
    <div id="copyright">
      <p>Copyright (C) <?php echo date('Y'); ?> Miyashita.</p>
    </div>
    <div id="wrapper">
      <header>
        <h1>チラうら</h1>
        <nav>
          <ul>
            <li id="write"><i class="fas fa-pen-nib" id="writeIcon"></i></li>
          </ul>
        </nav>
      </header>
      <main>
        <?php if(count($errors) !== 0): ?>
        <p id="errorTitle"><i class="fas fa-exclamation-triangle fa-fw"></i>しっぱいした<i class="fas fa-exclamation-triangle fa-fw"></i></p>
        <ul class="error_list">
          <?php foreach($errors as $value): ?>
          <li><i class="fas fa-dizzy fa-fw"></i> <?php echo htmlspecialchars($value,ENT_QUOTES,'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <div id="form">
          <form action="index.php" method="post" enctype="multipart/form-data">
            <dl>
              <dt>タイトルっぽいもの</dt>
              <dd><input type="text" name="title"></dd>
              <dt>かいたひと</dt>
              <dd><input type="text" name="screen_name"></dd>
              <dt>つれづれなるままに</dt>
              <dd><textarea name="comment" rows="8"></textarea></dd>
              <dd><input type="file" name="img"></dd>
              <dd><input type="submit" name="submit" value="送信"></dd>
            </dl>
          </form>
        </div>
        <?php
        //一覧の取得
        if($offset === 0):
          $sql = 'SELECT * FROM `post` ORDER BY `created_at` DESC LIMIT 5';
          $stmt=$pdo->prepare($sql);
          $stmt->execute();
        else:
          $sql = 'SELECT * FROM `post` ORDER BY `created_at` DESC LIMIT :offset,5';
          $stmt=$pdo->prepare($sql);
          $stmt -> bindParam(':offset',$offset);
          $stmt -> execute();
        endif;
        //$result = $stmt->fetch(PDO::FETCH_ASSOC);
        //if($result):
        $contents = array();
        while($post = $stmt -> fetch(PDO::FETCH_ASSOC)):
          $contents += array($post['id'] => $post['title']);
          ?>
        <section id="<?php echo $post['id']; ?>">
          <h2><?php echo htmlspecialchars($post['title'],ENT_QUOTES,'UTF-8'); ?></h2>
          <table>
            <tr>
              <td class="img" colspan="2">
                <a href="./img/<?php echo htmlspecialchars($post['img'],ENT_QUOTES,'UTF-8'); ?>">
                  <img src="./img/<?php echo htmlspecialchars($post['img'],ENT_QUOTES,'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'],ENT_QUOTES,'UTF-8'); ?>の添付画像">
                </a>
              </td>
            </tr>
            <tr>
              <td><?php echo nl2br(htmlspecialchars($post['comment'],ENT_QUOTES,'UTF-8')); ?></td>
            </tr>
            <tr>
              <td colspan="2" class="tdRight">ー<?php echo htmlspecialchars($post['screen_name'],ENT_QUOTES,'UTF-8'); ?></td>
            </tr>
            <tr>
              <td colspan="2" class="tdRight"><i class="far fa-clock fa-fw"></i><?php echo substr(htmlspecialchars($post['created_at'],ENT_QUOTES,'UTF-8'),2,14); ?></td>
            </tr>
          </table>
        </section>
          <?php
        endwhile;
          $stmt = null;
          $pdo = null;
        ?>
        <div id="pager">
          <?php if($page != 1): ?>
          <a href="?page=<?php echo $prev; ?>">&#171; 前へ</a>
          <?php else: ?>
            &#171; 前へ
          <?php endif; ?>
          <span>｜</span>
          <?php if($page < $totalPage): ?>
          <a href="?page=<?php echo $next; ?>">次へ &#187;</a>
          <?php else: ?>
          次へ &#187;
          <?php endif; ?>
        </div>
      </main>
    </div>
    <footer>
      <ul>
      <?php foreach($contents as $key => $value): ?>
        <li>
          <a href="#<?php echo $key; ?>"><i class="fas fa-ellipsis-v fa-fw"></i> <?php echo $value; ?></a>
        </li>
      <?php endforeach; ?>
      </ul>
    </footer>
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script>
      const write = document.getElementById("write");
      const writeIcon = document.getElementById("writeIcon");
      const form = document.getElementById("form");
      let flag=false;
      //メニュー開閉
      $("#write").click(function(){
        if(flag){
          $("#form").slideUp("fast","swing");
          writeIcon.style.color="rgb(0, 0, 0)";
          flag=!flag;
        }
        else{
          $("#form").slideDown("fast","swing");
          writeIcon.style.color="rgb(153, 153, 153)";
          flag=!flag;
        }
      });
    </script>
  </body>
</html>