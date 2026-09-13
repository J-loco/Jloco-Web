			<ol class="breadcrumb">
				<li><a href="<?= e(url()) ?>">Accueil</a></li>
				<li class="active">Inscription</li>
			</ol>

			<br /> <br /> <br />
			<div class="row">
				<div class="col-md-12">
					<!-- section -->
					<div class="row">
						<div class="col-md-6 col-md-offset-3 col-xs-12">
							<section class="section margin-top-20 margin-bottom-20 no-border">
								<h2 class="page-header text-center no-margin-top"><i class="ion-clipboard"></i> Incription a <?= e(TITLE) ?></h2>
									<?php
									if(isset($_POST['register'])) {
										$fields = ['username', 'email', 'password', 'repeat-password', 'question', 'answer', 'security-password'];
										$input = [];
										foreach($fields as $field)
											$input[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';

										$error = null;
										if(in_array('', $input, true)) {
											$error = 'Un des champs est invalide !';
										} else if(checkString($input['username']) || checkString($input['password']) || checkString($input['question']) || checkString($input['answer'])) {
											$error = 'Un des champs comporte des caractères indésirable !';
										} else if(!hash_equals($input['password'], $input['repeat-password'])) {
											$error = 'Les mots de passe ne sont pas identique !';
										} else if(!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
											$error = 'L\'email est invalide !';
										} else if(!StarLoco\Web\Captcha::verify($input['security-password'])) {
											$error = 'Le captcha ne correspond pas à celui de l\'image !';
										} else if(!isset($_POST['checkbox'])) {
											$error = 'Vous devez accepter les CGUS afin de vous incrire sur nos service.';
										}

										if($error === null) {
											$query = $login -> prepare("SELECT COUNT(*) FROM world_accounts WHERE account = ?;");
											$query -> execute([$input['username']]);
											$exists = (int) $query -> fetchColumn() > 0;
											$query -> closeCursor();

											if($exists) {
												$error = 'Le nom de compte existe déjà !';
											} else {
												$query = $login -> prepare("INSERT INTO world_accounts(account, pass, email, question, reponse, dateRegister) VALUES (?, ?, ?, ?, ?, ?);");
												$query -> execute([$input['username'], legacy_password_hash($input['password']), $input['email'], $input['question'], $input['answer'], date('d/m/y')]);
												$query -> closeCursor();

												flash('success', 'Ton compte a été créé avec succès, tu peux maintenant te connecter !');
												redirect(url('signin'));
											}
										}

										echo alert('danger', $error, 'Oh shit!') . '<br />';
									}
									?>
								<form method="POST" action="<?= e(url('register')) ?>">
									<?= csrf_field() ?>
									<div class="row">
										<div class="control-group col-md-6 col-xs-12">
											<label class="control-label" for="reg-username">Nom de compte</label>
											<div class="controls margin-top-5">
												<input type="text" class="form-control" id="reg-username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
											</div>
										</div>
										<div class="control-group col-md-6 col-xs-12">
											<label class="control-label" for="reg-email">Email</label>
											<div class="controls margin-top-5">
												<input type="text" class="form-control" id="reg-email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required="">
											</div>
										</div>
										<div class="control-group col-md-6 col-xs-12 margin-top-10">
											<label class="control-label" for="reg-password">Mot de passe</label>
											<div class="controls margin-top-5">
												<input type="password" class="form-control" id="reg-password" name="password" placeholder="" required="">
											</div>
										</div>
										<div class="control-group col-md-6 col-xs-12 margin-top-10">
											<label class="control-label" for="reg-repeat-password">Confirmation du mot de passe</label>
											<div class="controls margin-top-5">
												<input type="password" class="form-control" id="reg-repeat-password" name="repeat-password" placeholder="" required="">
											</div>
										</div>

										<div class="control-group col-md-6 col-xs-12 margin-top-10">
											<label class="control-label" for="reg-question">Question secrète</label>
											<div class="controls margin-top-5">
												<input type="text" class="form-control" id="reg-question" name="question" value="<?= e($_POST['question'] ?? '') ?>" required="">
											</div>
										</div>
										<div class="control-group col-md-6 col-xs-12 margin-top-10">
											<label class="control-label" for="reg-answer">Réponse secrète</label>
											<div class="controls margin-top-5">
												<input type="text" class="form-control" id="reg-answer" name="answer" placeholder="" required="">
											</div>
										</div>

										<div class="control-group col-md-12 col-xs-12">
											<label class="control-label margin-top-10" for="reg-security">Image de sécurité</label>

											<div class="control-label margin-top-10">
												<img src="<?= e(URL_SITE . 'img/captcha.php') ?>" alt="Captcha" title="Cliquer pour changer d'image" style="cursor: pointer;" onclick="this.src = this.src.split('?')[0] + '?' + Date.now();">
											</div>

											<div class="controls margin-top-5">
												<input type="text" class="form-control" id="reg-security" name="security-password" placeholder="" required="">
											</div>

										</div><br />
										<div style="margin-top: 15px;" class="control-group col-md-12 col-xs-12">
											<div class="checkbox pull-left no-padding no-margin-bottom margin-top-5">
												<input type="checkbox" id="reg-checkbox" name="checkbox">
												<label for="reg-checkbox">J'accepte les <a href="<?= e(url('cgu')) ?>">CGU</a>.</label>
											</div>
											<button type="submit" name="register" class="btn btn-success pull-right">S'inscrire</button>
										</div>
									</div>
								</form>
								<h2 class="page-header text-center no-margin-top"></h2>
							</section>

						</div>
					</div>
				</div>
			</div>
			<br /> <br /> <br /> <br />
			<!-- ./leftside -->
