			<!-- slideshow 
			<div class="bxslider-wrapper">
					<div id="bx-tabs">
						<div class="bx-nav pull-left col-md-3 no-padding"><a data-slide-index="0" href="#"><div class="bx-section"><h3>Le craft sécurisé</h3></div></a></div>
						<div class="bx-nav pull-left col-md-3 no-padding"><a data-slide-index="1" href="#"><div class="bx-section"><h3>L'IA parmis nous..</h3></div></a></div>
						<div class="bx-nav pull-left col-md-3 no-padding"><a data-slide-index="2" href="#"><div class="bx-section"><h3>Les fêtes s'annonces joyeuse</h3></div></a></div>
						<div class="bx-nav pull-left col-md-3 no-padding"><a data-slide-index="3" href="#"><div class="bx-section"><h3>L'île de noël ouverte</h3></div></a></div>
					</div>
				<div class="bxslider">
					<div class="img">
						<img src="img/slideshow/1.jpg" alt="" />
						<a href="#">
							<div class="caption">
								<h2 class="animated fadeInLeft">Global news</h2>
								<div class="clearfix"></div>
								<h1 class="animated fadeInRight">Phasellus cursus justo at diam bibendum</h1>
								<div class="clearfix"></div>
								<p class="animated fadeInLeft">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas egestas, orci id ullamcorper tempor, justo libero condimentum orci.</p>
							</div>
						</a>
					</div>
					<div class="img">
						<img src="img/slideshow/2.jpg" alt="" />
						<a href="#">
							<div class="caption">
								<h2>Xbox One</h2>
								<div class="clearfix"></div>
								<h1>Sed mauris elit, blandit vitae volutpat ne</h1> 
								<div class="clearfix"></div>
								<p>Susspendisse eu dui in gravida condimentum....</p>
							</div>
						</a>
					</div>
					<div class="img">
						<img src="img/slideshow/3.jpg" alt="" />
						<a href="#">
							<div class="caption">
								<h2>PS4</h2>
								<div class="clearfix"></div>
								<h1>Vestibulum ante ipsum primis in faucibus</h1>
								<div class="clearfix"></div>
								<p>Quisque vestibulum pretium quam, nec scelerisque....</p>
							</div>
						</a>
					</div>
					<div class="img">
						<img src="img/slideshow/4.jpg" alt="" />
						<a href="#">
							<div class="caption">
								<h2>PS4</h2>
								<div class="clearfix"></div>
								<h1>Donec pretium purus</h1>
								<div class="clearfix"></div>
								<p>Quisque vestibulum pretium quam, nec scelerisque....</p>
							</div>
						</a>
					</div>
				</div>
				<div class="bx-controls-direction"></div>
				<div id="bx-tabs">
					<div class="bx-nav">
						<a data-slide-index="0" href="#"></a>
						<a data-slide-index="1" href="#"></a>
						<a data-slide-index="2" href="#"></a>
						<a data-slide-index="3" href="#"></a>
					</div>
				</div>
			</div>
			 ./slideshow -->
			
			<div class="leftside">
				<ul class="section-title no-margin-top">
					<li><h3 style="font-family: Arial,sans-sherif!important;">Les dernières nouveautés</h3></li>
				</ul>
			
				
				<div style="width:100%!important;" class="col-md-9 col-xs-12" >
					<div class="row">
						<!-- 12 Columns -->
						<div class="col-md-12">
							<div class="alert alert-warning no-border-radius">
								Classé du plus récent au plus vieux, restez informer !
							</div>
							<ul class="timeline">
								<?php
								$newsCount = (int) $connection -> query('SELECT COUNT(*) FROM `website_timeline_news`;') -> fetchColumn();
								$pageCount = max(1, (int) ceil($newsCount / 10));
								$page = isset($_GET['num']) && ctype_digit((string) $_GET['num']) ? min(max(1, (int) $_GET['num']), $pageCount) : 1;

								$query = $connection -> prepare('SELECT * FROM `website_timeline_news` ORDER BY id DESC LIMIT :start, 10;');
								$query -> bindValue(':start', ($page - 1) * 10, PDO::PARAM_INT);
								$query -> execute();

								foreach($query -> fetchAll(PDO::FETCH_OBJ) as $i => $news) { ?>
									<li <?php if($i % 2) echo 'class="timeline-inverted"'; ?>>
										<div class="timeline-badge primary"></div>
										<div class="timeline-panel">
											<div class="timeline-heading">
												<h4 class="padding-15"><a href="#"><?= e($news -> title) ?></a></h4>
												<?php if(!empty($news -> img)) { ?>
													<img class="img-responsive full-width" src="<?= e($news -> img) ?>" alt="" />
												<?php } ?>
											</div>
											<div class="timeline-body">
												<?php // Written by an administrator: HTML is allowed on purpose. ?>
												<p><?= $news -> content ?></p>
											</div>
											<div class="timeline-footer">
												<i class="ion-android-calendar"></i> <?= e(convertDateToString((string) $news -> date)) ?>
												<a class="pull-right"><i class="ion-android-forums"></i>0</a>
											</div>
										</div>
									</li>
								<?php
								}
								?>

								<li class="clearfix" style="float: none;"></li>
							</ul>

							<center>
								<div class="btn-group">
									<a href="<?= $page > 1 ? e(url('index', ['num' => $page - 1])) : '#' ?>" class="btn btn-sm btn-default"><i class="fa fa-chevron-left"></i></a>
									<div class="btn btn-sm btn-default"><?= $page . " / " . $pageCount ?></div>
									<a href="<?= $page < $pageCount ? e(url('index', ['num' => $page + 1])) : '#' ?>" class="btn btn-sm btn-default"><i class="fa fa-chevron-right"></i></a>
								</div>
							</center>
						</div>
					</div>
				</div>
				<!-- ./12 Columns -->			
			</div>
			<!-- ./leftside -->			
