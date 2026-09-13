<?php
/** "12.000" -> "12%", "0.5" -> "0.5%" */
function drop_percent($value): string {
	return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.') . '%';
}

function drop_rate(object $drop): string {
	if($drop -> percentGrade5 < 1)
		return "<font color='red'>&lt;</font> 1%";
	if($drop -> percentGrade1 == $drop -> percentGrade5)
		return e(drop_percent($drop -> percentGrade1));
	return e(drop_percent($drop -> percentGrade1) . ' à ' . drop_percent($drop -> percentGrade5));
}

// Search by monster or by item; each result row groups all drops of one monster (or item).
$search = null;
if(isset($_POST['search1']) && is_string($_POST['monster'] ?? null)) {
	$search = ['label' => 'm.name', 'term' => $_POST['monster'], 'group' => 'monster'];
} else if(isset($_POST['search2']) && is_string($_POST['object'] ?? null)) {
	$search = ['label' => 'i.name', 'term' => $_POST['object'], 'group' => 'item'];
}

$rows = [];
if($search !== null && trim($search['term']) !== '') {
	// $search['label'] comes from the fixed list above, never from the request.
	$query = $jiva -> prepare("SELECT m.id AS monster_id, m.name AS monster_name, i.id AS item_id, i.name AS item_name, d.ceil, d.percentGrade1, d.percentGrade5
		FROM `drops` d
		JOIN `monsters` m ON m.id = d.monsterId
		JOIN `item_template` i ON i.id = d.objectId
		WHERE LOWER(" . $search['label'] . ") LIKE ?
		ORDER BY " . $search['label'] . " LIMIT 500;");
	$query -> execute(['%' . mb_strtolower(trim($search['term'])) . '%']);

	foreach($query -> fetchAll(PDO::FETCH_OBJ) as $drop) {
		$key = $search['group'] === 'monster' ? $drop -> monster_id : $drop -> item_id;
		$rows[$key]['name'] = $search['group'] === 'monster' ? $drop -> monster_name : $drop -> item_name;
		$rows[$key]['drops'][] = $drop;
	}
}
?>
			<div class="leftside">
				<ol class="breadcrumb">
					<li><a href="<?= e(url()) ?>">Accueil</a></li>
					<li class="active">Visualisateur de drop</li>
				</ol>
				<ol class="breadcrumb">
					<form method="post" class="form-inline" action="<?= e(url('viewdrop')) ?>">
						<?= csrf_field() ?>
						<input type="text" class="form-control" name="monster" style="border: 1px solid gray;" placeholder="Recherche du monstre.." value="<?= e($_POST['monster'] ?? '') ?>" />
						<button type="submit" class="btn btn-primary" name="search1"><i class="fa fa-search"></i></button>
					</form>
					<br />
					<form method="post" class="form-inline" action="<?= e(url('viewdrop')) ?>">
						<?= csrf_field() ?>
						<input type="text" class="form-control" name="object" style="border: 1px solid gray;" placeholder="Recherche de l'objet.." value="<?= e($_POST['object'] ?? '') ?>" />
						<button type="submit" name="search2" class="btn btn-primary"><i class="fa fa-search"></i></button>
					</form>
				</ol>

				<?php if($search !== null) { ?>
					<ol class="breadcrumb">
					<div class="box no-border-radius padding-20" style="border: 1px solid gray;">
						<table class="table table-striped no-margin">
							<thead>
								<tr>
									<th><?= $search['group'] === 'monster' ? 'Nom du monstre' : 'Nom de l\'objet' ?></th>
									<th><?= $search['group'] === 'monster' ? 'Nom de l\'objet' : 'Nom du monstre' ?></th>
									<th>Prospection</th>
									<th>Taux</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach($rows as $row) { ?>
								<tr>
									<td><?= e($row['name']) ?></td>
									<td><?php foreach($row['drops'] as $drop) echo e($search['group'] === 'monster' ? $drop -> item_name : $drop -> monster_name) . '<br />'; ?></td>
									<td><?php foreach($row['drops'] as $drop) echo e($drop -> ceil) . '<br />'; ?></td>
									<td><?php foreach($row['drops'] as $drop) echo drop_rate($drop) . '<br />'; ?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
					</ol>
				<?php } ?>
			</div>
			<!-- ./leftside -->
