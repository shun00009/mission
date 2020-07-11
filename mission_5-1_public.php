<?php
	$dsn = 'データベース名';
	$user = 'ユーザ名';
	$password = 'パスワード';
	$pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

	//データベース構成
	// DB:test
	// id_name_comment_date_exist_edit_passward
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>mission_5-1</title>
    </head>
    <body>
        <?php
            session_start();
			
			$number = 1;
            $date = date("Y/m/d H:i:s");
			$echo_check = 0;
			$pass_error = 0;
				
			//txt関連	
            $filename = "mission_3-5.txt";

			
			//エラー回避用
			if(!empty($_POST["name"])){
				$name = $_POST["name"];
			} else {
				$name = "";
			}
			if(!empty($_POST["coment"])){
				$coment = $_POST["coment"];
			} else {
				$coment = "";
			}
			if(!empty($_POST["i_password"])){
				$i_password = $_POST["i_password"];
			} else {
				$i_password = "";
			}
			if(!empty($_POST["e_password"])){
				$e_password = $_POST["e_password"];
			} else {
				$e_password = "";
			}
			if(!empty($_POST["edit_mode"])){
				$edit_mode = $_POST["edit_mode"];
			} else {
				$edit_mode = false;
			}
			if(!empty($_POST["edit_num"])){
				$edit_num = $_POST["edit_num"];
			} else {
				$edit_num = "";
			}
			
			//パスワードチェック用関数(編集番号、入力パスワード、動作モード（1:編集 2:削除）)
			function proc($e_num, $pass, $func){
				global $pre_name, $pre_comment, $pre_password;
				global $edit_mode;
				global $echo_check, $pass_error;
				global $pdo;
				
				//該当番号のデータを抽出
				$sql = 'SELECT * FROM db_test WHERE id=:id';
				$stmt = $pdo -> prepare($sql);
				$stmt -> bindParam(':id', $e_num, PDO::PARAM_INT);
				$stmt -> execute();
				$result = $stmt -> fetchALL();
				
				//パスワード照合
				if($pass == $result[0]['password']){
					switch ($func){
						//編集情報を変数に格納
						case 1:
							if($result[0]['exist'] == 1){
								$_SESSION["number"] = $result[0]['id'];
								$pre_name = $result[0]['name'];
								$pre_comment = $result[0]['comment'];
								$edit_mode = true;
								$echo_check = 0;
								return true;
							} else {
								$edit_mode = false;
								$echo_check = 1;	//削除されていることを表示
								return true;
							}
						
						case 2:
							return true;
					}
				}else{
					//パスワード不一致
					$pass_error = 1;
					return false;
				}
			}
		
			//投稿番号のチェック
			$sql = 'SELECT max(id) AS id_max FROM db_test';
			$stmt = $pdo -> query($sql);
			$result = $stmt -> fetchAll();
			$number = $result[0]['id_max'];
			if(empty($number)){
				$number = 1;
			} else{
				++$number;
			}
    		
			//トークンの照合
			if(!empty($_REQUEST["check"]) && !empty($_SESSION["check"])){
				if($_REQUEST["check"] == $_SESSION["check"]){
					$do_check = true;
				}else{
					$do_check = false;
				}
			} else{
				$do_check = false;
			}
			//トークンの更新
			$_SESSION["check"] = $check = mt_rand();
			
			//編集モード
			if(!empty($_REQUEST["edit"])){
				if($_REQUEST["edit"]){
					//編集が押されたときの処理
					if($edit_mode){
						$edit_mode = false;
					} else if(0<$edit_num || $_edit_num < $number){
							//指定した番号の書き込みが存在する場合、書き込み情報を変数に格納
							proc($edit_num, $e_password, 1);
						} else{
							//指定した番号の書き込みが存在しない場合
							$echo_check = 3;	//書き込みが存在しないことを表示
							$edit_mode = false;
						}
						
				} else if($edit_mode == false){
					//編集ボタンが押されておらず、編集モードではないとき
					$edit_mode = false;
					$echo_check = 0;	//現時点では出力無し
				}
			}
			
			/*****	送信が押されたときの処理		*****/
			if(!empty($_REQUEST["submit"])){
				if($_REQUEST["submit"]){
					/*****	新規投稿時の処理		*****/
					if($edit_mode == false){
						//DBへの書き込み（トークンによって多重投稿を禁止している）
						if($do_check){
							if(empty($name) || empty($coment)){
								$echo_check = 4;
							} else {
								$echo_check = 5;
								$sql = "INSERT INTO db_test (name, comment, date, exist, edit, password) VALUES(:name, :comment, now(), 1, 0, :password)";
								$stmt = $pdo -> prepare($sql);
								$params = array(':name' => $name, ':comment' => $coment, 'password' => $i_password);
								$stmt -> execute($params);
							}
						} else{
							$echo_check = 6;
						}
						
					} else if($edit_mode ==true){
						/*****	編集モード中の編集処理 *****/
						//トークンが一致している場合は編集の処理を行う
						if($do_check){
							$sql = 'UPDATE db_test SET name=:name, comment=:comment, date=now(), exist=1, edit=1, password=:password WHERE id=:id';
							$stmt = $pdo -> prepare($sql);
							$stmt -> bindParam(':name', $name, PDO::PARAM_STR);
							$stmt -> bindParam(':comment', $coment, PDO::PARAM_STR);
							$stmt -> bindParam(':password', $i_password, PDO::PARAM_STR);
							$stmt -> bindParam(':id', $_SESSION["number"], PDO::PARAM_INT);
							$stmt -> execute();
							
						//トークンが一致しない場合は処理を行わずに更新する
						} else {
							$echo_check = 6;
						}
					}
					
					$edit_mode = false;
				}
			}
					
				/*****	削除が押されたときの処理		*****/
			if(!empty($_REQUEST["delete"])){	
				if($_REQUEST["delete"]){
					//トークンが一致するとき
					if($do_check){
						//削除番号が存在し、パスワードが一致するとき
						if($edit_num < $number && proc($edit_num, $e_password, 2)){
							$sql = 'UPDATE db_test SET date=now(), exist=0 WHERE id=:id';
							$stmt = $pdo -> prepare($sql);
							$stmt -> bindParam(':id', $edit_num, PDO::PARAM_INT);
							$stmt -> execute();

						}else{
							//入力番号の書き込みが存在しない場合
							$echo_check = 10;
						}	
					} else {
						$echo_check = 9;
					}

					$edit_mode = false;
				}
			}
						
        ?>
        <p>コメント欄の作成（DB使用）</p>
        <br>
        <hr>
		<?php
			if($edit_mode){
				echo "<b>編集モード</b><br>"; 
				echo "　　${edit_num}の投稿を編集します。<br>";
				echo "　　修正後、「編集」を押してください。修正をやめる場合は、「新規投稿モードへ戻る」を押してください（変更は保存されません）。<br>"; 
				echo "　　なお、<b>パスワード欄には新しいパスワード</b>を入力してください。次回の編集で使用します。<br><br>";
			} else{
				echo "<b>新規投稿モード</b><br>";
				echo "　　名前とコメントを入力して、「新規投稿」を押してください。<br>";
				echo "　　既存の投稿を削除または編集したい場合には、書き込み番号を入力して「削除」か「編集モードへ」を押してください。<br><br>";
			}
		?>
        <form action="" method="post" enctype="multipart/form-data">
			<input type="hidden" name = "check" value="<?=$check?>">
			<input type="hidden" name = "edit_mode" value="<?=$edit_mode?>">
            <input type="text" name = "name" placeholder="名前" value="<?php
					if($edit_mode){echo $pre_name;}
					else{ echo ""; }
			?>">
            <input type="text" name = "coment" placeholder="コメント" value="<?php
					if($edit_mode){echo $pre_comment;}
					else{ echo ""; }
			?>">
			<input type="text" name = "i_password" placeholder="パスワード">
            <input type="submit" name="submit" value="<?php
					if(!$edit_mode){echo "新規投稿";}
					else { echo "編集"; }
			?>"><br>
			<input type="number" name="edit_num" placeholder="投稿番号">
			<input type="text" name="e_password" placeholder="パスワード">
			<input type="submit" name="delete" value="削除">
			<input type="submit" name="edit" value="<?php
					if($edit_mode){echo "新規投稿モードへ戻る";}
					else{ echo "編集モードへ"; }
			?>">
		</form>
        <br>
		
        <?php
			//メッセージ出力
			switch($echo_check){
				case 1:
					echo "投稿は削除されています<br>";
					break;
				case 2:
					echo "ERROR:投稿が見つかりませんでした<br>";
					break;
				case 3:
					echo "指定された投稿は存在しません<br>";
					break;
				case 4:
					echo "上部のフォームに名前とコメントを入力してください。<br>";
					break;
				case 5:
					echo "送信を受けつけました。<br>";
					echo "送信内容：${name} ${coment}<br>";
					break;
				case 6:
					echo "表示を更新しました。<br>";
					break;
				case 7:
					echo "編集完了しました。<br>";
					break;
				case 8:
					echo "削除完了しました。<br>";
					echo "削除内容：<br>${d_echo1}<br>${d_echo2}";
					break;
				case 9:
					echo "更新エラーです。<br>";
					break;
				case 10:
					echo "指定の書き込みは存在しません。<br>";
					break;
			}
			
			if($pass_error == 1){
				echo "パスワードが一致しません";
			}
			
			/*****	コメントログの表示	*****/
			/**********************************************
			言わずもがなここももちろんDBへ移行
			**********************************************/
			$sql = 'SELECT * FROM db_test';
			$stmt = $pdo -> query($sql);
			$results = $stmt -> fetchALL();
			echo "<hr>【コメントログ】<br>";
			foreach($results as $row){
				if($row['exist'] == 1){
					if($row['edit'] == 0){
						echo "${row['id']}:<b>${row['name']}</b>:${row['date']}<br>";
						echo "　　<b>${row['comment']}</b><br><br>";
					} else{
						echo "${row['id']}:<b>${row['name']}</b>:${row['date']}（編集済み）<br>";
						echo "　　<b>${row['comment']}</b><br><br>";
					}
				}else{
					echo "＜投稿は削除されています＞<br><br>";
				}
			}
				
            $_SESSION["name"] = $name;
			$_SESSION["coment"] = $coment;
            $name = "";
            $coment = "";
        ?>
    </body>
</html>