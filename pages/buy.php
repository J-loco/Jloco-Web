<?php
require_login();

// Game databases the shop can deliver to, by website_shop_objects.server (see shop.php).
$gameDatabases = [1 => $jiva];

$accountId = (int) $_SESSION['id'];
$template = isset($_GET['template']) && ctype_digit((string) $_GET['template']) ? (int) $_GET['template'] : 0;
$server = isset($_GET['server']) && ctype_digit((string) $_GET['server']) ? (int) $_GET['server'] : 0;

$query = $login -> prepare("SELECT o.template, o.name, o.price, o.jp, o.category, c.name AS category_name, t.skin, t.level, t.effects, s.name AS server_name
	FROM `website_shop_objects` o
	JOIN `website_shop_objects_templates` t ON t.id = o.template
	LEFT JOIN `website_shop_categories` c ON c.id = o.category
	LEFT JOIN `world_servers` s ON s.id = o.server
	WHERE o.template = ? AND o.server = ? AND o.active = 1;");
$query -> execute([$template, $server]);
$object = $query -> fetch(PDO::FETCH_OBJ);
$query -> closeCursor();

if(!$object || !isset($gameDatabases[$server])) {
	flash('danger', 'Cet objet n\'est pas en vente.');
	redirect(url('shop'));
}

if(isset($_POST['confirm'])) {
	$price = (int) $object -> price;

	// Atomic: the row is only updated if the account still has enough points, so two
	// simultaneous purchases cannot spend the same points twice.
	$debit = $login -> prepare("UPDATE world_accounts SET points = points - ? WHERE guid = ? AND points >= ?;");
	$debit -> execute([$price, $accountId, $price]);

	if($debit -> rowCount() !== 1) {
		flash('danger', 'Tu n\'as pas assez de points pour acheter cet objet.');
		redirect(url('buy', ['template' => $template, 'server' => $server]));
	}

	try {
		// Same format as Account.addGift in StarLoco-Game: "template,quantity,jp" joined by ";".
		$gift = $template . ',1,' . ((int) $object -> jp);
		$game = $gameDatabases[$server];
		$game -> prepare("INSERT IGNORE INTO gifts (id, objects) VALUES (?, '');") -> execute([$accountId]);
		$game -> prepare("UPDATE gifts SET objects = IF(objects = '', ?, CONCAT(objects, ';', ?)) WHERE id = ?;") -> execute([$gift, $gift, $accountId]);
	} catch(PDOException $e) {
		$login -> prepare("UPDATE world_accounts SET points = points + ? WHERE guid = ?;") -> execute([$price, $accountId]);
		error_log('StarLoco-Web: gift delivery failed for account ' . $accountId . ': ' . $e -> getMessage());
		flash('danger', 'Une erreur s\'est produite, tes points ont été remboursés.');
		redirect(url('buy', ['template' => $template, 'server' => $server]));
	}

	$login -> prepare("INSERT INTO `website_shop_objects_purchases` (account, template, quantite, server, date) VALUES (?, ?, 1, ?, NOW());") -> execute([$accountId, $template, $server]);

	flash('success', 'L\'achat s\'est effectué avec succès, ton objet t\'attend en jeu !');
	redirect(url('shop', ['server' => $server, 'category' => (int) $object -> category]));
}

ob_start();
if($object -> effects !== '')
	convertStatsToString($object -> effects);
$effectsTooltip = ob_get_clean() ?: 'Aucun';
?>
			<div class="leftside">
				<ol class="breadcrumb">
					<li><a href="<?= e(url()) ?>">Accueil</a></li>
					<li class="active">Achat</li>
				</ol>
				<div class="alert alert-info no-border-radius" role="alert">
					Vous êtes sur le point d'acheter un(e) <?= e($object -> category_name) ?> sur le serveur <strong><?= e($object -> server_name ?: TITLE) ?></strong> !
				</div>

				<div class="section section-default padding-25" data-toggle="tooltip" title="" data-original-title="<?= e($effectsTooltip) ?>" data-html="true">
					<div style="display:inline-flex;">
						<object class="thumbnail" style="height: 140px;width: 140px;" type="application/x-shockwave-flash" data="<?= e(URL_SITE . 'img/dofus/swf/items/item.swf') ?>">
							<param name="allowscriptaccess" value="always">
							<param name="flashvars" value="item=<?= e(URL_SITE . 'img/dofus/swf/items/' . (int) $object -> category . '/' . (int) $object -> skin . '.swf') ?>"/>
							<param name="wmode" value="transparent"/>
						</object>
						<div style="margin-left: 25px;">
							<strong>Informations :</strong><br>
							Nom : <?= e($object -> name) ?><br>
							Level : <?= e($object -> level) ?><br>
							Prix : <?= e($object -> price) ?><br>
							Jet maximum : <?= $object -> jp ? "Oui." : "Non." ?><br>
						</div>
					</div>
				</div>
				<form method="post" action="<?= e(url('buy', ['template' => $template, 'server' => $server])) ?>">
					<?= csrf_field() ?>
					<button type="submit" name="confirm" class="btn btn-lg btn-block btn-info btn-outline">Êtes-vous sûr de vouloir acheter cet objet ?</button>
				</form>
			</div>
			<!-- ./leftside -->
