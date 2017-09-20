<!doctype html>
<html>
<head>
	<link rel="stylesheet" href="{{Backoffice|Asset|css/style.css}}" type="text/css">
</head>
<body class="machine-backoffice-plugin-admin">
	<div class="wrapper">
		<header>
			header
		</header>
		<nav>
			<div class="container">
				<ul>	
					<?php foreach ($tables as $t => $label) { ?>
					<li class="<?php echo $t == $tablename ? "active" : ""; ?>"><a class="button" href="<?php echo $Backoffice->GetLink("/$t/list/1/"); ?>"><?php echo $label; ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</nav>
		<main>
			<?php if (isset($records)) { ?>
			<section id="sectionlist">
				<div class="buttonbar">
					<a href="<?php echo $Backoffice->GetLink("/$tablename/list/1/"); ?>" class="buttonbar-item button minibutton">First</a>
					<a 
						<?php if ($p > 1) { ?>href="<?php echo $Backoffice->GetLink("/$tablename/list/" . ($p - 1) . "/"); ?>"<?php } ?>
						class="buttonbar-item button minibutton <?php if ($p <= 1) { ?>disabled<?php } ?>">
							Previous
					</a>
					<div class="buttonbar-item"><input value="<?php echo $p; ?>" /></div>
					<div class="buttonbar-item minibutton">/ <?php echo $maxp; ?></div>
					<a 
						<?php if ($p < $maxp) { ?>href="<?php echo $Backoffice->GetLink("/$tablename/list/" . ($p + 1) . "/"); ?>"<?php } ?>
						class="buttonbar-item button minibutton <?php if ($p >= $maxp) { ?>disabled<?php } ?>">
							Next
					</a>
					<a href="<?php echo $Backoffice->GetLink("/$tablename/list/" . ($maxp) . "/"); ?>" class="buttonbar-item button minibutton">Last</a>
					<div class="buttonbar-item sep"></div>
					<form action="<?php echo $Backoffice->GetLink("/api/record/$tablename/"); ?>" method="post">
						<button class="buttonbar-item button minibutton">Add</button>
					</form>
				</div>
				<div id="listtable">
					<div class="container">
						<table>
							<thead>
								<tr>
									<?php
									foreach ($records as $id => $r) {
										foreach ($r as $fieldname => $fieldvalue) {
											$orderlink = $Backoffice->GetLink("/$tablename/$fieldname/updateordeorder/");
											$filterlink = $Backoffice->GetLink("/$tablename/$fieldname/updatefilter/");
											?>
											<th>
												<a href="<?php echo $orderlink; ?>"><?php echo $fieldname; ?></a><br>
												<form action="<?php echo $filterlink; ?>" method="POST">
													<?php echo $Backoffice->getFilterControl($tablename, $fieldname); ?>
													<button>filter</button>
												</form>
											</th>
											<?php
										}
										break;
									}
									?>
								</tr>
							</thead>
							<tbody>
								<?php foreach($records as $id => $r) { ?>
								<tr id="row<?php echo $id;?>">
									<?php 
									$first = true;
									foreach ($r as $fieldname => $fieldvalue) {
										if ($first) {
											?>
											<td class="first">
												<a href="<?php echo $Backoffice->GetLink("/$tablename/" . $fieldvalue . "/"); ?>" class="button minibutton button100">
													<?php echo $fieldvalue; ?>
												</a>
											</td>
											<?php
											$first = false;
										} else {
											$content = $Backoffice->renderFieldInList($tablename, $fieldname, $fieldvalue);
											?>
											<td><?php echo $content; ?></td>
											<?php
										}
									} 
									?>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="buttonbar">
					<a href="<?php echo $Backoffice->GetLink("/$tablename/list/1/"); ?>" class="buttonbar-item button minibutton">First</a>
					<a 
						<?php if ($p > 1) { ?>href="<?php echo $Backoffice->GetLink("/$tablename/list/" . ($p - 1) . "/"); ?>"<?php } ?>
						class="buttonbar-item button minibutton <?php if ($p <= 1) { ?>disabled<?php } ?>">
							Previous
					</a>
					<div class="buttonbar-item"><input value="<?php echo $p; ?>" /></div>
					<div class="buttonbar-item minibutton">/ <?php echo $maxp; ?></div>
					<a 
						<?php if ($p < $maxp) { ?>href="<?php echo $Backoffice->GetLink("/$tablename/list/" . ($p + 1) . "/"); ?>"<?php } ?>
						class="buttonbar-item button minibutton <?php if ($p >= $maxp) { ?>disabled<?php } ?>">
							Next
					</a>
					<a href="<?php echo $Backoffice->GetLink("/$tablename/list/" . ($maxp) . "/"); ?>" class="buttonbar-item button minibutton">Last</a>
					<div class="buttonbar-item sep"></div>
					<form action="<?php echo $Backoffice->GetLink("/api/record/$tablename/"); ?>" method="post">
						<button class="buttonbar-item button minibutton">Add</button>
					</form>
				</div>
			</section>
			<?php } ?>
			<?php if (isset($record)) { ?>
			<section id="sectiondetail">
				<form action="<?php echo $Backoffice->GetLink("/api/record/$tablename/$id/"); ?>" method="post" enctype="multipart/form-data">
					<div class="buttonbar">
						<a href="<?php echo $Backoffice->GetLink("/$tablename/list/1/"); ?>" class="buttonbar-item button minibutton">Back to list</a>
					</div>
					<div id="recorddetail">
						<div class="container">
							<div class="pad">
								<?php foreach ($record as $field => $value) { ?>
								<div class="formrow">
									<div class="formlabel">
										<?php echo $field; ?>
									</div>
									<div class="formfield">
										<?php $Backoffice->renderField($tablename, $field, $value); ?>
									</div>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
					<div class="buttonbar">
						<button class="button minibutton">Save</button>
					</div>
				</form>
			</section>
			<?php } ?>
		</main>
		<footer>
			footer
		</footer>
	</div>
</body>
</html>