			<div class="leftside">
					<ol class="breadcrumb">
						<li><a href="<?= e(url()) ?>">Accueil</a></li>
						<li class="active">News</li>
					</ol>	
				<div class="row">
					<div class="col-md-12 col-xs-12">
						<section class="no-border no-padding-top">
							<div class="page-header margin-top-10"><h4>Quoi de neuf ?</h4></div>
							
								<?php
								require_once("./include/rsslib.php");
								$feed = RSS_Display(URL_RSS_NEWS_IPB, 15, false, true);
								echo $feed !== '' ? $feed : alert('info', 'Aucune nouvelle du forum pour le moment.');
								?>
							
							
						</section>
					</div>
				</div>			
			</div>
			<!-- ./leftside -->			