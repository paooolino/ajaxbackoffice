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
			<div class="container">
				<ul>	
					<?php foreach ($tables as $t) { ?>
					<li><a href="<?php echo $Link->Get("/$t/list/1/"); ?>"><?php echo $t; ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</nav>
		<main>
			<div class="pagination">pagination</div>
			<div id="listtable">
				<div class="container">
					<table>
						<thead>
							<tr>
								<?php
								foreach ($records as $id => $record) {
									foreach ($record as $fieldname => $fieldvalue) {
										?><th><?php echo $fieldname; ?></th><?php
									}
									break;
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php foreach($records as $id => $record) { ?>
							<tr>
								<?php foreach ($record as $fieldname => $fieldvalue) { ?>
									<td><?php echo $fieldvalue; ?></td>
								<?php } ?>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="pagination">pagination</div>
		</main>
		<footer>
			footer
		</footer>
	</div>
	<script src="bundle.js"></script>
</body>
</html>