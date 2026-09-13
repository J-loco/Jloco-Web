<?php
if(!is_admin()) {
	http_response_code(403);
	redirect(url());
}

$gameNewsTypes = ['News' => 'Nouvelle', 'Event' => 'Evénement', 'Update_fr' => 'Mise à jour', 'Maintenance' => 'Maintenance'];

if(isset($_POST['webNews'])) {
	$title = trim((string) ($_POST['title'] ?? ''));
	$content = trim((string) ($_POST['content'] ?? ''));
	if($title !== '' && $content !== '') {
		// The _en/_es columns have no default: reuse the French text until translations exist.
		$login -> prepare("INSERT INTO `website_timeline_news` (author, title, title_en, title_es, content, content_en, content_es, date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW());")
			-> execute([$_SESSION['user'], $title, $title, $title, $content, $content, $content]);
		flash('success', 'La nouvelle a été ajoutée avec succès !');
	}
	redirect(url('administration'));
}

if(isset($_POST['deleteWebNews'])) {
	$login -> prepare("DELETE FROM `website_timeline_news` WHERE `id` = ?;") -> execute([(int) $_POST['deleteWebNews']]);
	flash('success', 'La nouvelle a été supprimée avec succès !');
	redirect(url('administration'));
}

if(isset($_POST['gameNews'])) {
	$title = trim((string) ($_POST['title'] ?? ''));
	$type = (string) ($_POST['type'] ?? '');
	if($title !== '' && isset($gameNewsTypes[$type])) {
		// client_rss_news.id is not AUTO_INCREMENT in the login schema.
		$login -> prepare("INSERT INTO `client_rss_news` (id, title_fr, title_en, date, icon, link) SELECT COALESCE(MAX(id), 0) + 1, ?, ?, ?, ?, '' FROM `client_rss_news`;")
			-> execute([$title, $title, (int) date('Ymd'), $type]);
		flash('success', 'La nouvelle a été ajoutée avec succès !');
	}
	redirect(url('administration'));
}

if(isset($_POST['deleteGameNews'])) {
	$login -> prepare("DELETE FROM `client_rss_news` WHERE `id` = ?;") -> execute([(int) $_POST['deleteGameNews']]);
	flash('success', 'La nouvelle a été supprimée avec succès !');
	redirect(url('administration'));
}
?>
			<div class="leftside">
					<ol class="breadcrumb">
						<li><a href="<?= e(url()) ?>">Accueil</a></li>
						<li class="active">Panel d'administration</li>
					</ol>
				<div class="row">
					<div class="col-md-12 col-xs-12">
						<section class="no-border no-padding-top">
							<div class="page-header margin-top-10"><h4>Gestions des web news</h4></div>
							<div class="section section-default padding-25">
								<form method="post" action="<?= e(url('administration')) ?>">
									<?= csrf_field() ?>
									<div class="col-md-12 col-xs-12 no-padding">
										<div class="control-group col-md-12 no-padding">
											<div class="controls">
												<input type="text" class="form-control" name="title" placeholder="Titre" required>
											</div>
										</div>
									</div>
									<div class="control-group col-md-12 no-padding col-xs-12 margin-top-15">
										<div class="controls">
											<textarea class="form-control" name="content" rows="3" style="max-width: 100%;" placeholder="Votre contenu.. (HTML autorisé)"></textarea>
										</div>
									</div>
									<button type="submit" class="btn btn-success btn-outline pull-left margin-top-15" name="webNews" style="width:100%;">Envoyer</button>
								</form>
							</div>
						</section>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12 col-xs-12">
						<section class="section section-white no-border no-padding-top">
						<div class="section section-default padding-25">
							<div class="box no-border-radius">
								<table class="table table-striped no-margin">
									<thead>
										<tr>
											<th class="padding-left-15">#</th>
											<th>Titre</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach($login -> query("SELECT id, title FROM `website_timeline_news` ORDER BY `id` DESC;") -> fetchAll(PDO::FETCH_OBJ) as $row) { ?>
										<tr>
											<td class="padding-left-15"><?= (int) $row -> id ?></td>
											<td><?= e($row -> title) ?></td>
											<td>
												<form method="post" action="<?= e(url('administration')) ?>" style="display: inline;" onsubmit="return confirm('Supprimer cette nouvelle ?');">
													<?= csrf_field() ?>
													<button type="submit" name="deleteWebNews" value="<?= (int) $row -> id ?>" class="btn btn-danger btn-outline btn-sm" data-toggle="tooltip" title="" data-original-title="Supprimer"><i class="ion-trash-b"></i></button>
												</form>
											</td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
							</div>
						</section>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12 col-xs-12">
						<section class="no-border no-padding-top">
							<div class="page-header margin-top-10"><h4>Gestions des game news</h4></div>
							<div class="section section-default padding-25">
								<form method="post" action="<?= e(url('administration')) ?>">
									<?= csrf_field() ?>
									<div class="col-md-12 col-xs-12 no-padding">
										<div class="control-group col-md-12 no-padding">
											<div class="controls">
												<input type="text" class="form-control" name="title" placeholder="Titre" maxlength="50" required>
											</div>
										</div>
									</div>
									<div class="control-group col-md-12 no-padding col-xs-12 margin-top-15">
										<div class="controls">
											<select name="type" class="form-control" required>
												<option value="" disabled selected>Intitulé</option>
												<?php foreach($gameNewsTypes as $value => $label) { ?>
													<option value="<?= e($value) ?>"><?= e($label) ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<button type="submit" class="btn btn-success btn-outline pull-left margin-top-15" name="gameNews" style="width:100%;">Envoyer</button>
								</form>
							</div>
						</section>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12 col-xs-12">
						<section class="section section-white no-border no-padding-top">
						<div class="section section-default padding-25">
							<div class="box no-border-radius">
								<table class="table table-striped no-margin">
									<thead>
										<tr>
											<th class="padding-left-15">#</th>
											<th>Titre</th>
											<th>Icone</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach($login -> query("SELECT id, title_fr, icon FROM `client_rss_news` ORDER BY `id` DESC;") -> fetchAll(PDO::FETCH_OBJ) as $row) { ?>
										<tr>
											<td class="padding-left-15"><?= (int) $row -> id ?></td>
											<td><?= e($row -> title_fr) ?></td>
											<td><?= e($gameNewsTypes[$row -> icon] ?? $row -> icon) ?></td>
											<td>
												<form method="post" action="<?= e(url('administration')) ?>" style="display: inline;" onsubmit="return confirm('Supprimer cette nouvelle ?');">
													<?= csrf_field() ?>
													<button type="submit" name="deleteGameNews" value="<?= (int) $row -> id ?>" class="btn btn-danger btn-outline btn-sm" data-toggle="tooltip" title="" data-original-title="Supprimer"><i class="ion-trash-b"></i></button>
												</form>
											</td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
							</div>
						</section>
					</div>
				</div>
			</div>
			<!-- ./leftside -->
