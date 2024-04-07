<?php

// Get info about profile from URL as an objects
if (!empty($_GET['profile'])) {
	$url = $twtsURL;
}

$profile = getTwtsFromTwtxtString($url);
$profileURL = $baseURL . '/?profile=' . $profile->mainURL;
$textareaValue = "@<$profile->nick $profile->mainURL> ";

// TODO: Move this to twtxt.php or base.php
//       and make nav for general timeline to filter on posts vs. replies + all

// Filter posts vs. replies
$pattern = "/\(#\w{7}\)/";

$twt_replies = array_filter($twts, function ($twt) use ($pattern) {
	return preg_match($pattern, $twt->originalTwtStr);
});

$twt_posts = array_filter($twts, function ($twt) use ($pattern) {
	return !preg_match($pattern, $twt->originalTwtStr);
});

// Get active view/filter
$is_gallery = str_contains(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "gallery");
$is_replies = str_contains(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "replies");

if ($is_gallery) {
	$posts_active = "";
	$replies_active = "";
	$gallery_artive = "class='active'";  
} elseif ($is_replies) {
	$twts = $twt_replies;
	$posts_active = "";
	$replies_active = "class='active'";
	$gallery_artive = "";
} else {
	$twts = $twt_posts;
	$posts_active = "class='active'";
	$replies_active = "";
	$gallery_artive = "";
}

?>

<div class="profile">

  <a href="<?=$profile->avatar?>">
	<img class="avatar" src="<?=$profile->avatar?>" onerror="this.onerror=null;this.src='<?= $baseURL ?>/media/default.png';">
  </a>

  <div>
	  <a href="<?=$profileURL?>" class="author">
		<strong><?=$profile->nick?></strong>@<?=parse_url($profile->mainURL, PHP_URL_HOST);?>
	  </a>

	<p><?=$profile->description?></p>

	<small>
	  <span class="filters">
		<a <?=$posts_active?> href="<?=$profileURL?>" >Posts</a>
		<a <?=$replies_active?> href="<?=$baseURL?>/replies?profile=<?=$profile->mainURL?>" >Replies</a>
		<a <?=$gallery_artive?> href="<?=$baseURL?>/gallery?profile=<?=$profile->mainURL?>" >Gallery</a>
	  </span>

	  <span class="right">
		<!-- <a href="following.php">Following <?php echo count($twtFollowingList); ?></a> |  -->
		<a target="_blank" href="<?=$profile->mainURL?>"><?=$profile->mainURL?></a>
		(<a href="https://yarn.social">How to follow</a>)
	  </span>

	  <div class="tagcloud">
		<?php include_once 'partials/tag_cloud.php'; ?>
	  </div>

	</small>

  </div>

</div>