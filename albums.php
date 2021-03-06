<?php
//ini_set("display_errors","1");
//ini_set("display_startup_errors","1");
//ini_set('error_reporting', E_ALL);
session_start();
include("config.php");
include("includes/user.php");

if(!isset($_GET['act']) and !isset($_GET['user']) and !isset($_FILES['pic']) and isset($_SESSION['user_id']))
{
Header("Location: albums.php?user={$_SESSION['user_id']}");
}
if(isset($_GET['delete']))
{
	$db->connect();
	$db->action("UPDATE albums SET delete=TRUE WHERE user_id={$_SESSION['user_id']} AND album_id={$_GET['delete']}");
	$db->action("DELETE FROM feed WHERE user_id={$_SESSION['user_id']} AND value2='{$_GET['delete']}'");
	$db->close();
}
if(isset($_GET['idelete']))
{
	$db->connect();
	$db->action("SELECT seq FROM images WHERE user_id={$_SESSION['user_id']} AND name='{$_GET['idelete']}'");
	while($result=pg_fetch_array($db->result)) $seq=$result['seq'];
	$db->action("UPDATE images SET delete=TRUE WHERE user_id={$_SESSION['user_id']} AND name='{$_GET['idelete']}'");
	$db->action("UPDATE images SET seq=0 WHERE user_id={$_SESSION['user_id']} AND name='{$_GET['idelete']}'");
	$db->action("UPDATE images SET seq=seq-1 WHERE user_id={$_SESSION['user_id']} AND album_id='{$_GET['album']}' AND seq>{$seq};");
	$db->close();
	Header("Location: albums.php?user={$_GET['user']}&album={$_GET['album']}");
	exit();
}
echo $start;
echo "<title>{$lang['albums']}</title>";
echo $after_title;
echo "<script type=\"text/javascript\" src=\"jquery.tablednd_0_5.js\"></script>
<script>
function view(image) {";
echo "	$('#vis').css('display','inline');
	$('#view').css('display','inline');
	$('#view').html('<a href=\"#!{$_GET['user']}/'+next[image]+'\"><img src=\"./p/'+image+'.jpg\" /></a>');
	}
	var js_title='{$lang['albums']}';
	$(document).ready(function(){
	var i = $('input').size() + 1;
	
	$('#add').click(function() {
		$('<input type=\"file\" name=\"pic[]\"><br>').fadeIn('fast').appendTo('.inputs');
		i++;
	});

	if(window.location.hash)
	{
		view2(location.hash.replace('#!',''));
	}
	});
$(window).bind('hashchange', function() { 
	if(window.location.hash)
	{
		view2(window.location.hash.replace('#!',''));
	} else
	{
		$('#vis').css('display','none');
		$('#view').css('display','none');
	}
});

function view2(image)
{
	$.ajax({
		type: \"POST\", 
		url: \"actions/albums/view.php\",
		data: \"&user={$_GET['user']}&image=\"+image,
		cache: false,
		success: function(html) {
			if(html!='')
			{
				$(\"#view\").html(html);
				$('#vis').css('display','inline');
				if($('#view').css('display')=='inline') {} else $('#view').css('display','inline');
			}
		}
	});
}
function like()
{
	$.ajax({
		type: \"GET\", 
		url: \"actions/albums/like.php\",
		data: \"&act=like&user_id={$_GET['user']}&name=\"+location.hash.replace('#!',''),
		cache: false,
		success: function(html) {
			if(html!='')
			{
				$('#like_inf').html(html);
			}
		}
	});
}
function dislike()
{
	$.ajax({
		type: \"GET\", 
		url: \"actions/albums/like.php\",
		data: \"&act=dislike&user_id={$_GET['user']}&name=\"+location.hash.replace('#!',''),
		cache: false,
		success: function(html) {
			if(html!='')
			{
				$('#like_inf').html(html);
			}
		}
	});
}

function comment() 
{
	$.ajax({  
		type: \"GET\",  
		url: \"actions/albums/comment.php\",
		data: \"&user_photo={$_GET['user']}&image=\"+location.hash.replace('#!','')+\"&comment=\"+$(\"#comment\").val(),  
		success: function(html){
		$(\"#comments\").html(html);
		}  
	});
}


$(document).ready(function(){
// ---------
$(\"#albums\").tableDnD({
  onDragClass: \"dragRow\",
  onDrop: function(table, row) {
    var rows = table.tBodies[0].rows;
    var messageString = \"Перемещена строка \" + row.id + \"<br />Новый порядок сортировки: \";
    for (var i=0; i<rows.length; i++) {
      messageString += rows[i].id + \" \";
    }
    $(\"#messageArea\").html(messageString);
    $(\"#albums\").find(\"td[@id='\"+ row.id +\"']\").fadeOut(700, function () {
      $(this).fadeIn(300);
    });
  },
  onDragStart: function(table, row) {
    $(\"#messageArea\").html(\"Перемещаем строку \" + row.id);
  }
});
// ---------
});
	</script>";
echo $after_scripts;
echo "<div id=\"vis\" onclick=\"$('#vis').css('display','none'); $('#view').css('display','none'); location.href='#';\"></div>";

function imageresize($outfile,$infile,$neww,$newh,$quality,$type)
	{
	if($type == "image/jpeg")
	{
	$im=imagecreatefromjpeg($infile);
	} 
	elseif($type == "image/png" or $type == "image/x-png")
	{
	$im=imagecreatefrompng($infile);
	} 
	elseif($type == "image/gif")
	{
	$im=imagecreatefromgif($infile);
	}
	elseif($type == "image/bmp")
	{
	$im=imagecreatefrombmp($infile);
	}
	$k1=$neww/imagesx($im);
	$k2=$newh/imagesy($im);
	$k=$k1>$k2?$k2:$k1;

	$w=intval(imagesx($im)*$k);
	$h=intval(imagesy($im)*$k);

	$im1=imagecreatetruecolor($w,$h);
	if($type == "image/png" or $type == "image/x-png")
	{
	imagealphablending($im1, false);
	imagesavealpha($im1, true);
	}
	imagecopyresampled($im1,$im,0,0,0,0,$w,$h,imagesx($im),imagesy($im));
	imagejpeg($im1,$outfile,$quality);
	imagedestroy($im);
	imagedestroy($im1);
}

if(isset($_SESSION['user_id']))
{

if(isset($_GET['user']))
{
	$db->connect();
	$user=user_array($_GET['user']);
	
	if(!isset($_GET['album']))
	{
		$db->action("SELECT * FROM albums WHERE user_id={$_GET['user']} AND delete=FALSE ORDER BY seq");
		echo "<div class=\"main_top\"><a href=\"{$_GET['user']}\">{$user['name']} {$user['lastname']}</a> -> {$lang['albums']}</div>";
		echo "<table id=\"albums\">";
		if(pg_num_rows($db->result)==0)
		{
			echo "{$lang['no_albums']}";
		} else 
		{
			$i=1;
			echo "<div id=\"messageArea\"></div>";
			while($album=pg_fetch_array($db->result))
			{
			$name=$album['name'];
			$album_id=$album['album_id'];
			$count=$album['count'];
			$cover=$album['cover'];
			echo "<tr id=\"{$i}\"><td><a href=\"?user={$_GET['user']}&album={$album_id}\"><img src=\"./i/{$_GET['user']}/{$cover}.jpg\"></a></td><td><a href=\"?user={$_GET['user']}&album={$album_id}\">{$name}</a><br>";
			if($_GET['user']==$_SESSION['user_id'])
			{
				echo "<div id=\"delete{$album_id}\" onclick=\"$(this).css('display','none'); $('#sure{$album_id}').css('display','inline');\">
				<a href=\"#\" style=\"font-size:12;\">remove</a></div>
				<div id=\"sure{$album_id}\" style=\"font-size:12;display:none;\">
				<a href=\"?delete={$album_id}\"style=\"font-size:12;\">yes</a>
				<a onclick=\" $('#sure{$album_id}').css('display','none'); $('#delete{$album_id}').css('display','inline');\" style=\"font-size:12;\" href=\"#\">no</a>";
			}
			echo "</div></td></tr>";
			$i++;
			}
		}
		echo "</table>";
	} else {
		$db->action("SELECT name FROM albums WHERE user_id={$_GET['user']} AND album_id={$_GET['album']}");
		if(pg_num_rows($db->result)!=0)
		{
			$album_exist=
			$db->action("SELECT name FROM images WHERE user_id={$_GET['user']} AND album_id={$_GET['album']} AND delete=FALSE ORDER BY seq");
			$i=0;
			while($images=pg_fetch_array($db->result))
			{
				$image[]=$images['name'];
				$i++;
			}
			$db->action("SELECT name FROM albums WHERE user_id={$_GET['user']} AND album_id={$_GET['album']};");
			while($album=pg_fetch_array($db->result)) $name=$album['name'];
			echo "<div class=\"main_top\"><a href=\"{$_GET['user']}\">{$user['name']} {$user['lastname']}</a> -> <a href=\"albums.php?user={$_GET['user']}\">{$lang['albums']}</a> -> {$name}</div>";
			//////////////////////////////
			echo "<table><tr>";
			for($x=0; $x<$i; $x++)
			{
			if($_GET['user']==$_SESSION['user_id'] and isset($_GET['edit'])) echo "<a href=\"?user={$_SESSION['user_id']}&album={$_GET['album']}&idelete={$image[$x]}\">delete</a>";
			echo "<td class=\"album_photo\" onclick=\"location.href='#!{$image[$x]}';\"><a href=\"#!{$image[$x]}\" onclick=\"if(navigator.userAgent.toLowerCase().indexOf('firefox/3.5')!=-1) view2('{$image[$x]}')\"><img src=\"./i/{$_GET['user']}/{$image[$x]}.jpg\"></a></td>";
			if($x % 4 === 0 and $x!=0) echo "</tr><tr>";
			}
		
			echo "</tr></table><div id=\"view\"></div>";
		} else {
			echo "ALBUM DOES NOT EXIST";
		}
	}
	
	$db->close();
} else {
	if($_FILES['pic'])
	{
		$db->connect();
		$db->action("SELECT count FROM albums WHERE user_id={$_SESSION['user_id']}");
		while($albums=pg_fetch_array($db->result)) $count=$albums['count'];
		$z=0;
		foreach($_FILES['pic'] as $file) 
		{
		$z++;
		}
		echo "<table><form action=\"albums.php\" method=\"POST\">";
		for($i=0; $i<=$z; $i++)
		{
			$file = $_FILES['pic']['tmp_name'][$i];
			$type = $_FILES['pic']['type'][$i];
			if(is_uploaded_file($file))
			{
				$seq=$i+1;
				$db->action("SELECT images FROM counts WHERE user_id={$_SESSION['user_id']}");
				while($images=pg_fetch_array($db->result)) $uid=$images['images']+1;
				$db->action("UPDATE counts SET images='{$uid}' WHERE user_id={$_SESSION['user_id']}");
				$name = chr( rand(97, 122) ).chr( rand(97, 122) ).chr( rand(97, 122) ).chr( rand(97, 122) ).$uid;
				if($i==0) $db->action("UPDATE albums SET cover='{$name}' WHERE user_id={$_SESSION['user_id']} AND album_id={$_POST['album_id']}");
				$db->action("INSERT INTO images (user_id,album_id,name,seq) VALUES ({$_SESSION['user_id']}, {$_POST['album_id']}, '{$name}', {$seq});");
				move_uploaded_file($file, "./p/{$_SESSION['user_id']}/{$name}.jpg");
				imageresize("./i/{$_SESSION['user_id']}/{$name}.jpg","./p/{$_SESSION['user_id']}/{$name}.jpg",100,100,90, $type);
				imageresize("./s/{$_SESSION['user_id']}/{$name}.jpg","./p/{$_SESSION['user_id']}/{$name}.jpg",800,600,90, $type);
				//list($width, $height, $type) = getimagesize("./s/{$name}");
				echo "<tr><td><img src=\"./i/{$_SESSION['user_id']}/{$name}.jpg\"></td><td><textarea name=\"description{$name}\"></textarea></td></tr>";
				$count++;
			}
		}
		echo "<tr><td colspan=\"2\"><input type=\"submit\" value=\"{$lang['save']}\"></td></tr>
				<input type=\"hidden\" name=\"act\" value=\"edit\"></form></table>";
		$db->action("UPDATE albums SET count=count+{$count} WHERE user_id={$_SESSION['user_id']} AND album_id={$_POST['album_id']}");
		$db->action("INSERT INTO feed (user_id,type,value1,value2) VALUES ({$_SESSION['user_id']}, 'photos', '{$count}', '{$_POST['album_id']}');");
		$db->close();
		
	} 
	if(isset($_POST['name']) and isset($_POST['description']))
	{
		$db->connect();
		$db->action("SELECT albums FROM counts WHERE user_id={$_SESSION['user_id']}");
		while($counts=pg_fetch_array($db->result))
		{
			$album_id=$counts['albums']+1;
		}
		$db->action("INSERT INTO albums (user_id,name,description,album_id) VALUES ({$_SESSION['user_id']},'{$_POST['name']}','{$_POST['description']}',{$album_id});");
		$db->action("UPDATE counts SET albums={$album_id}");
		$db->close();
		echo "{$_GET['name']}<br>";
		echo "{$lang['upload_some_photos']}:<br>";
		echo "<form  method=\"POST\" enctype=\"multipart/form-data\" action=\"albums.php\">";
		echo "<div class=\"inputs\">";
		echo "<input type=\"file\" name=\"pic[]\" multiple=\"true\"><br>";
		echo "</div>";
		echo "<a href=\"#\" id=\"add\">add input</a><br>";
		echo "<input type=\"submit\" value=\"{$lang['submit']}\">";
		echo "<input type=\"hidden\" value=\"{$album_id}\" name=\"album_id\">";
		echo "</form>";
	}

	if($_GET['act']=="new" and !isset($_POST['name']))
	{
		echo "<form method=\"POST\" action=\"albums.php?act=new\">";
		echo "{$lang['name_album']}: <input type=\"text\" name=\"name\"><br>";
		echo "{$lang['description']}: <input type=\"text\" name=\"description\"><br>";
		echo "<input type=\"submit\" value=\"{$lang['save']}\">";
		echo "</form>";
	}
}
if($_GET['user']==$_SESSION['user_id'])
{
	echo "<a href=\"?act=new\">{$lang['new_album']}</a><br>";
}
}

echo $close;
?>
