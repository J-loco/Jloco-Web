			<ol class="breadcrumb">
				<li><a href="<?= e(url()) ?>">Accueil</a></li>
				<li class="active">Connexion</li>
			</ol>

			<br /> <br /> <br />
			<div class="row">
				<div class="col-md-12">
					<!-- section -->
					<div class="row">
						<div class="col-md-6 col-md-offset-3 col-xs-12">
							<section class="section margin-top-20 margin-bottom-20 no-border">
									<h2 class="page-header text-center no-margin-top"><i class="fa fa-sign-in"></i> Connexion a <?= e(TITLE) ?></h2>
									<!-- The login itself is handled in index.php, results are shown as flash messages. -->
									<form autocomplete="off" method="POST" action="<?= e(url('signin')) ?>">
										<?= csrf_field() ?>
										<input type="text" class="form-control" name="username" placeholder="Nom de compte" required="">
										<span class="help-block"></span>
										<input type="password" class="form-control" name="password" placeholder="Mot de passe" required="">
										<div class="margin-top-20">
											<div class="checkbox pull-left no-padding no-margin-bottom margin-top-5">
												<input type="checkbox" id="remember" name="remember">
												<label for="remember">Se souvenir de moi</label>
											</div>
											<button type="submit" name="login" class="btn btn-success pull-right">Connexion</button>
										</div>
									</form>
									<a href="<?= e(url('password')) ?>" class="text-dark margin-top-20 padding-top-20 help-block border-top-light btn-icon-right"><i class="fa fa-unlock"></i> Mot de passe oublié ?</a>
							</section>
						</div>
					</div>
				</div>
			</div>
			<br /> <br /> <br /> <br />
			<!-- ./leftside -->
