<?php
/**
 * Returns fn(int $mapId): ?object{name: ?string, x: string, y: string} locating a character.
 * maps.mappos is "x,y,subAreaId" (game DB); sub-area names live in world_base_sub_areas (login DB).
 */
function sidebar_locator(PDO $login, PDO $game): Closure {
	$maps = $game -> prepare('SELECT mappos FROM maps WHERE id = ?;');
	$subAreas = $login -> prepare('SELECT name FROM world_base_sub_areas WHERE id = ?;');

	return function (int $mapId) use ($maps, $subAreas): ?object {
		$maps -> execute([$mapId]);
		$mappos = $maps -> fetchColumn();
		$maps -> closeCursor();
		if ($mappos === false)
			return null;

		[$x, $y, $subAreaId] = array_pad(explode(',', (string) $mappos), 3, '');
		$subAreas -> execute([(int) $subAreaId]);
		$name = $subAreas -> fetchColumn();
		$subAreas -> closeCursor();

		return (object) ['name' => $name === false ? null : $name, 'x' => $x, 'y' => $y];
	};
}
?>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v2.4";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<!-- sidebar -->
			<div class="sidebar">
				<a href="<?= e(url('vote')) ?>" class="btn btn-warning btn-block btn-md btn-bold margin-bottom-15">Vote&nbsp; <i class="fa fa-sign-in"></i> &nbsp;RPG-Paradize</a>
				
				<!-- section -->
				<div class="section section-default">
					<div class="title dark-grey no-margin padding-10-15">
						<i class="ion-game-controller-a"></i> Etat des serveurs
					</div>
									
					<div class="tab-content padding-15">
						<ul id="Connexion" class="tab-pane active clearfix box">
							<!-- row -->
							<li class="row"> 
								<div class="col-md-2 no-padding"><img src="./img/servers/login.jpg"/></div>
								<div class="details col-md-10 no-padding-right">
									<div class="pull-left">
										<h5><a href="#">Serveur de connexion</a></h5>
										Connecté(s) : <?php 
										echo (int) $login -> query('SELECT COUNT(*) FROM world_players WHERE logged = 1;') -> fetchColumn(); 
										?>
									</div>
									
									<?php if(checkState(LOGIN_IP, LOGIN_PORT)) $state = "success"; else $state = "danger"; ?>
									
									<span class="label label-<?php echo $state ?> pull-right">
										<?php 
										if($state == "success") 
											echo '<i class="glyphicon glyphicon-ok"></i>'; 
										else 
											echo '<i class="glyphicon glyphicon-remove"></i>'; ?>
									</span>
								</div>
							</li>
						</ul>
						
						<ul id="Jiva" class="tab-pane active clearfix box">
							<!-- row -->
							<li class="row"> 
								<div class="col-md-2 no-padding"><img src="./img/servers/jiva.jpg"/></div>
								<div class="details col-md-10 no-padding-right">
									<div class="pull-left">
										<h5><a href="#" style="color: #2a5d9f">Jiva - Ankalike</a></h5>
										Connecté(s) : <?php 
										echo (int) $login -> query('SELECT COUNT(*) FROM world_players WHERE logged = 1 AND server = 601;') -> fetchColumn(); 
										?>
										<div class="info">
											<?php
											$query = $login -> prepare('SELECT * FROM world_servers WHERE `id` = 601;');
				                            $query -> execute();
											$query -> setFetchMode(PDO:: FETCH_OBJ);
											$server = $query -> fetch();
											$query -> closeCursor();
											echo "Uptime : " . ($server ? e(convertTimestampToUptime((int) $server -> uptime)) : '-');
											?>
										</div>
									</div>
									
									<?php if(checkState(JIVA_IP, JIVA_PORT)) $state = "success"; else $state = "danger"; ?>
									
									<span class="label label-<?php echo $state; ?> pull-right">
										<?php 
										if($state == "success") 
											echo '<i class="glyphicon glyphicon-ok"></i>'; 
										else 
											echo '<i class="glyphicon glyphicon-remove"></i>'; ?>
									</span>
								</div>
							</li>
						</ul>
					</div>
				</div>
				<!-- ./section -->
								
				<!-- section -->
				<div class="section section-default">
					<div class="title dark-grey no-margin padding-10-15">
						<i class="ion-podium"></i> Statistique
					</div>
					<div class="padding-15">
						<ul class="box no-padding">
							<li class="no-padding-top no-padding-bottom"><br />
								<div class="facebook-like-box">
									<?php
									$inscris = (int) $login -> query('SELECT COUNT(*) FROM world_accounts;') -> fetchColumn();
				
									$guildes = (int) $login -> query('SELECT COUNT(*) FROM `world_guilds`;') -> fetchColumn();
									
									$objets = (int) $login -> query('SELECT COUNT(*) FROM `world_objects`;') -> fetchColumn();
									
									$personnages = (int) $login -> query('SELECT COUNT(*) FROM world_players;') -> fetchColumn(); ?>
									
									<h4>Inscris&nbsp; : <?php echo $inscris; ?></h4>
									<br/><h4>Guildes&nbsp; : <?php echo $guildes; ?></h4>
									<br/><h4>Objets&nbsp; : <?php echo $objets; ?></h4>
									<br/><h4>Personnages&nbsp; : <?php echo $personnages; ?></h4>
								</div>
							<br /></li>
						</ul>
					</div>
				</div>
				<!-- ./section -->	
				
				<div class="section carousel-tab section-info">
					<div class="title no-margin">
						<i class="ion-ribbon-a" ></i> TOP 3 JOUEURS
					</div>
					<div class="jcarousel" data-jcarousel="true" data-jcarouselautoscroll="true">
						<ul class="box" style="left: 0px; top: 0px;">	
							<?php 

							$query = $login -> prepare('SELECT p.name, p.class, p.level, p.sexe, p.map, a.showOrHidePos FROM world_players p JOIN world_accounts a ON a.guid = p.account WHERE p.groupe = -1 ORDER BY p.xp DESC LIMIT 3;');
							$query -> execute();
							$query -> setFetchMode(PDO:: FETCH_OBJ);

							$locate = sidebar_locator($login, $jiva);
							$podium = [
								1 => ['F5C553', ' est le seul à être performant sur son expérience.'],
								2 => ['D1D1E3', ' essaye de prendre possésion de la première place malgrè ça légére infériorité.'],
								3 => ['E48644', ' à l\'amibition d\'être à la première place, même s\'il lui reste pas mal de travail !'],
							];

							$i = 1;

							while($row = $query -> fetch()) {
								[$color, $sentence] = $podium[$i];

								$position = '';
								if($row -> showOrHidePos) {
									$place = $locate((int) $row -> map);
									if($place && $place -> name !== null)
										$position = ' Il traverse en ce moment même ' . e($place -> name) . '.';
								}

								echo '<li class="no-padding"><h4 class="padding-15"><a href="#"><i class="ion-trophy" style="color: #' . $color . ';"></i>
								' . $i . '<sup>er</sup> Joueur : ' . htmlspecialchars($row -> name) . '</a></h4><div class="padding-15"><p>
								Un jeune ' . convertClassIdToString($row -> class, $row -> sexe) . ', de niveau ' . $row -> level . $sentence . $position . '</p></div></li>';

								$i++;
							}
							$query -> closeCursor();
							?>
						</ul>
					</div>
					<div class="jcarousel-pagination" data-jcarouselpagination="true"><a href="#1" class="active">1</a><a href="#2" class="">2</a></div>
				</div>
				
				<?php 
				$i = (int) $login -> query('SELECT COUNT(*) FROM world_players WHERE groupe = -1 AND deshonor > 0;') -> fetchColumn();
				if($i > 0) {
					?>
					<div class="section carousel-tab section-warning">
						<div class="title text-dark no-margin">
							<i class="ion-nuclear"></i><?php echo '<span style="color: red">' . $i . '</span>'; ?> Recherché(s) ! 
						</div>
						<div class="jcarousel" data-jcarousel="true" data-jcarouselautoscroll="true">
							<ul class="box" style="left: 0px; top: 0px;">	
								<?php
								$query = $login -> prepare('SELECT * FROM world_players WHERE groupe = -1 AND deshonor > 0 ORDER BY deshonor DESC LIMIT 0, 5;');
								$query -> execute();
								$query -> setFetchMode(PDO:: FETCH_OBJ);
								
								$locate ??= sidebar_locator($login, $jiva);
								while($row = $query -> fetch()) {
									$place = $locate((int) $row -> map);
									?><li class="no-padding clearfix">
										<h4 class="padding-15"><a href="#"><?= e($row -> name . ' - Niveau ' . $row -> level) ?> </a></h4>

										<div class="padding-15">
											<p>Un satané <b><?= e(convertClassIdToString($row -> class, $row -> sexe)) ?></b> c'est attaqué à un jeune aventurier sans défense, ni compagnie.
											Cet <?= $row -> sexe == 0 ? 'homme' : 'femme' ?> mérite une correction ! </p>
											<p>
											<?php
											if($row -> logged == 1 && $place && $place -> name !== null)
												echo "Cet personne a été récement vue à travers " . e($place -> name) . " ( <b>" . e($place -> x) . " ; " . e($place -> y) . "</b>) !";
											else
												echo "Nous ne possédons pour l'instant aucune information concernant sa position..";
											?>
											</p>
										</div>
									</li><?php
								}
								$query -> closeCursor();	
								?>
							</ul>
						</div>
						<div class="jcarousel-pagination" data-jcarouselpagination="true"><a href="#1" class="active">1</a><a href="#2" class="">2</a></div>
					</div>
				<?php
				} ?>
				<!-- section -->
				<div class="section section-default">
					<div class="title dark-grey no-margin padding-10-15">
						<i class="ion-thumbsup"></i> Facebook
					</div>
					<div class="padding-15">
						<ul class="box no-padding">
							<li class="no-padding-top no-padding-bottom">
								<div class="fb-page" data-href="https://www.facebook.com/Aestia-447842705416732" data-width="300" data-height="281" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false" data-show-posts="true"></div>						
							</li>
						</ul>
					</div>
				</div>
				<!-- ./section -->
			</div><!-- ./sidebar -->
