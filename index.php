
<?php include "header.php" ?>

<?php
	if (isset($_SESSION['username']))
	{
		// extract(init());
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
