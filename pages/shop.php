<?php
require_login();

// Shop items are attached to a game database index (website_shop_objects.server), not to a
// world_servers id. Only index 1 ($jiva) exists; see docs/refactor/audit.md (D13).
$servers = [];
$query = $login -> query("SELECT DISTINCT o.server AS id, s.name FROM website_shop_objects o LEFT JOIN world_servers s ON s.id = o.server WHERE o.active = 1 ORDER BY o.server;");
foreach($query -> fetchAll(PDO::FETCH_OBJ) as $row)
	$servers[(int) $row -> id] = $row -> name ?: TITLE;

$server = isset($_GET['server']) && ctype_digit((string) $_GET['server']) && isset($servers[(int) $_GET['server']]) ? (int) $_GET['server'] : -1;
$category = isset($_GET['category']) && ctype_digit((string) $_GET['category']) ? (int) $_GET['category'] : -1;

/** Item effects as tooltip HTML (convertStatsToString echoes instead of returning). */
function shop_effects_tooltip(string $effects): string {
	if($effects === '')
		return 'Aucun';
	ob_start();
	convertStatsToString($effects);
	return ob_get_clean();
}
?>
			<div class="leftside">
				<ol class="breadcrumb">
					<li><a href="<?= e(url()) ?>">Accueil</a></li>
					<li class="active">Boutique</li>
				</ol>

				<!-- Selecter of server -->
				<form class="form-inline" role="form" method="get" action="<?= e(url('shop')) ?>" style="margin-bottom: 10px;">
					<?= route_fields('shop') ?>
					<select class="form-control" style="width: 90%!important; height: 35px; padding: 6px 12px!important;" name="server">
						<?php foreach($servers as $id => $name) { ?>
							<option value="<?= $id ?>" <?= $id === $server ? 'selected' : '' ?>><?= e($name) ?></option>
						<?php } ?>
					</select>
					<button type="submit" class="btn btn-info">Ok</button>
				</form>
				<!-- End selecter of server -->

				<!-- Selecter of category -->
				<?php
				if($server != -1) {
					$query = $login -> prepare("SELECT DISTINCT c.id, c.name FROM `website_shop_categories` c JOIN `website_shop_objects` o ON o.category = c.id WHERE c.active = 1 AND o.active = 1 AND o.server = ? ORDER BY c.name;");
					$query -> execute([$server]);
					$categories = $query -> fetchAll(PDO::FETCH_OBJ); ?>
					<form class="form-inline" role="form" method="get" action="<?= e(url('shop')) ?>" style="margin-bottom: 10px;">
						<?= route_fields('shop') ?>
						<input type="hidden" name="server" value="<?= $server ?>">
						<select class="form-control" style="width: 90%!important; height: 35px; padding: 6px 12px!important;" name="category">
							<?php foreach($categories as $row) { ?>
								<option value="<?= (int) $row -> id ?>" <?= (int) $row -> id === $category ? 'selected' : '' ?>><?= e($row -> name) ?></option>
							<?php } ?>
						</select>
						<button type="submit" class="btn btn-info">Ok</button>
					</form>

					<div class="alert alert-info no-border-radius" role="alert">
						Les achats seront effectués sur le serveur <strong><?= e($servers[$server]) ?></strong> !
					</div>
				<?php
					if($category != -1) {
						$query = $login -> prepare("SELECT o.name, o.price, o.jp, t.id AS template, t.level, t.skin, t.effects FROM `website_shop_objects` o JOIN `website_shop_objects_templates` t ON t.id = o.template WHERE o.category = ? AND o.server = ? AND o.active = 1 ORDER BY o.price DESC;");
						$query -> execute([$category, $server]);
						$objects = $query -> fetchAll(PDO::FETCH_OBJ);

						if($objects) { ?>
						<section class="section section-white no-border no-padding-top">
							<div class="box no-border-radius padding-20">
							<table class="table no-margin">
								<thead>
									<tr>
										<th>#</th>
										<th>Nom</th>
										<th>Level</th>
										<th>Prix</th>
										<th>Jet maximum</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
								<?php foreach($objects as $object) { ?>
									<tr data-toggle="tooltip" title="" data-original-title="<?= e(shop_effects_tooltip((string) $object -> effects)) ?>" data-html="true">
										<td>
											<object class="thumbnail" style="height: 120px;width: 120px;" type="application/x-shockwave-flash" data="<?= e(URL_SITE . 'img/dofus/swf/items/item.swf') ?>">
												<param name="allowscriptaccess" value="always">
												<param name="flashvars" value="item=<?= e(URL_SITE . 'img/dofus/swf/items/' . $category . '/' . (int) $object -> skin . '.swf') ?>"/>
												<param name="wmode" value="transparent"/>
											</object>
										</td>
										<td><?= e($object -> name) ?></td>
										<td><?= e($object -> level) ?></td>
										<td><?= e($object -> price) ?></td>
										<td>
											<span class="btn btn-<?= $object -> jp ? 'success' : 'danger' ?> btn-outline btn-circle btn-xs"><i class="ion-<?= $object -> jp ? 'checkmark' : 'close' ?>"></i></span>
										</td>
										<td>
											<a href="<?= e(url('buy', ['template' => (int) $object -> template, 'server' => $server])) ?>"><span class="btn btn-info btn-outline btn-circle btn-xs" data-toggle="tooltip" title="" data-original-title="Acheter"><i class="fa fa-credit-card"></i></span></a>
										</td>
									</tr>
								<?php } ?>
								</tbody>
							</table>
							</div>
						</section>
						<?php
						} else { ?>
							<div class="alert alert-info no-border-radius" role="alert">
								<strong>Oh shit!</strong> Aucun objet est en vente pour ce serveur ainsi que cet catégorie.
							</div>
						<?php
						}
					}
				} ?>
				<!-- End selecter of category -->
			</div>
			<!-- ./leftside -->
