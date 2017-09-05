<!doctype html>
<html>
<head>
	<link rel="stylesheet" href="{{templatePath}}../../css/style.css" type="text/css">
</head>
<body>
	<div class="wrapper">
		<header>
			header
		</header>
		<nav>

		</nav>
		<main>
			<div class="pad">
				<?php 
				switch ($errtype) {
					case "too-big":
					case "upload-err-ini-size":
						?>
						<p>Attenzione: il file caricato è troppo grande. Il massimo limite consentito è di <?php echo ini_get("upload_max_filesize"); ?>.</p>
						<?php
						break;
					default:
						?>
						<p>Si è verificato un errore.</p>
						<?php
				}
				?>
				<p class="buttonbar"><a href="javascript:history.back();" class="buttonbar-item button minibutton">Torna indietro</a></p>
			</div>
		</main>
		<footer>
			footer
		</footer>
	</div>
	<script src="bundle.js"></script>
</body>
</html>