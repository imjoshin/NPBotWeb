
<?php include "header.php" ?>

<?php
	if (isset($_SESSION['username']))
	{
		// extract(init());
		?>

		<?php
	}
	else
	{
		include 'views/login.php';
	}
?>

<?php include "footer.php" ?>
