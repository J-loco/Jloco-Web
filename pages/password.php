			<ol class="breadcrumb">
				<li><a href="<?= e(url()) ?>">Accueil</a></li>
				<li class="active">Mot de passe oublié ?</li>
			</ol>

			<br /> <br /> <br />
			<div class="row">
				<div class="col-md-12">
					<!-- section -->
					<div class="row">
						<div class="col-md-6 col-md-offset-3 col-xs-12">
							<section class="section margin-top-20 margin-bottom-20 no-border">
									<h2 class="page-header text-center no-margin-top"><i class="fa fa-sign-in"></i> Réinitialisation de mot de passe</h2>
									<?php
									// Two steps: 1) account name, 2) secret answer. The account chosen in step 1 is kept
									// in the session, so step 2 cannot be pointed at another account.
									$step = 'account';
									$resetAccount = $_SESSION['password_reset'] ?? null;

									if(isset($_POST['next1'])) {
										$query = $login -> prepare("SELECT account, question FROM world_accounts WHERE account = ?;");
										$query -> execute([(string) ($_POST['account'] ?? '')]);
										$row = $query -> fetch(PDO::FETCH_OBJ);
										$query -> closeCursor();

										if($row) {
											$_SESSION['password_reset'] = $resetAccount = $row -> account;
											$step = 'answer';
										} else {
											echo alert('danger', 'Le nom de compte est incorrecte.', 'Oh shit!') . '<br />';
										}
									} else if(isset($_POST['change']) && $resetAccount !== null) {
										if(throttle_blocked($login, 'password_reset')) {
											echo alert('danger', throttle_message(), 'Oh shit!') . '<br />';
										} else {
											$query = $login -> prepare("SELECT 1 FROM world_accounts WHERE `account` = ? AND `reponse` = ?;");
											$query -> execute([$resetAccount, (string) ($_POST['answer'] ?? '')]);
											$ok = (bool) $query -> fetchColumn();
											$query -> closeCursor();

											if($ok) {
												throttle_clear($login, 'password_reset');
												unset($_SESSION['password_reset']);

												$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
												$newPass = '';
												for($i = 0; $i < 10; $i++)
													$newPass .= $alphabet[random_int(0, strlen($alphabet) - 1)];

												$query = $login -> prepare("UPDATE world_accounts SET `pass` = ? WHERE `account` = ?;");
												$query -> execute([legacy_password_hash($newPass), $resetAccount]);
												$query -> closeCursor();

												$step = 'done';
												echo "<div class='alert alert-success no-border-radius no-margin' style='text-align: center!important;' role='alert'><strong>Oh good!</strong> Votre nouveau mot de passe est désormais : " . e($newPass) . "</div><br />";
											} else {
												throttle_fail($login, 'password_reset');
												echo alert('danger', 'La réponse secrète est incorrecte.', 'Oh shit!') . '<br />';
												$step = 'answer';
											}
										}
									}

									if($step === 'answer') {
										$query = $login -> prepare("SELECT account, question FROM world_accounts WHERE account = ?;");
										$query -> execute([$resetAccount]);
										$row = $query -> fetch(PDO::FETCH_OBJ);
										$query -> closeCursor(); ?>
										<form autocomplete="off" method="POST" action="<?= e(url('password')) ?>">
											<?= csrf_field() ?>
											<input type="text" class="form-control" value="<?= e($row -> account) ?>" disabled>
											<span class="help-block"></span>
											<input type="text" class="form-control" value="<?= e($row -> question) ?>" disabled>
											<span class="help-block"></span>
											<input type="text" class="form-control" name="answer" placeholder="Votre réponse..">

											<div class="margin-top-20">
												<center>
													<button type="submit" name="change" class="btn btn-success pull-mid">Modifier</button>
												</center>
											</div>
										</form>
									<?php
									} else if($step === 'account') { ?>
										<form autocomplete="off" method="POST" action="<?= e(url('password')) ?>">
											<?= csrf_field() ?>
											<input type="text" class="form-control" name="account" placeholder="Nom de compte" required="">
											<span class="help-block"></span>

											<div class="margin-top-20">
												<center>
													<button type="submit" name="next1" class="btn btn-success pull-mid">Suivant</button>
												</center>
											</div>
										</form>
									<?php
									} ?>

							</section>
						</div>
					</div>
				</div>
			</div>
			<br /> <br /> <br /> <br /><br /> <br /> <br /> <br /><br /> <br /> <br /> <br /><br /> <br /> <br /> <br />
			<!-- ./leftside -->
