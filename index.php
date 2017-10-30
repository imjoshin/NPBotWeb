
<?php include "header.php"; ?>
<?php require_once(BASE_PATH . "utils.php"); ?>

<?php
	if (isset($_SESSION['username']))
	{
		extract(init());
		include 'views/gamelist.php';
		?>

		<div class="btn btn-logout">Logout</div>
		<?php
	}
	else
	{
		include 'views/login.php';
	}
?>

<?php include "footer.php" ?>
