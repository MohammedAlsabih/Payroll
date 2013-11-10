            <div class="account-wall">
                <form class="user-form" id="user-login" name="user-login" action="<?php echo base_url(); ?>user/login" method="post">
                    <input type="text" id="username" name="username" class="form-control" placeholder="Username" autofocus>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password">
                    <a href="<?php echo base_url(); ?>user/reset" class="reset">Reset password?</a>
                    <button id="signin" name="signin" class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
<?php if (isset($notify)): ?>
                    <div class="alert alert-danger text-center"><?php echo $notify; ?></div>
<?php endif; ?>
                </form>
            </div>
            <a href="<?php echo base_url(); ?>user/register" class="text-center bottom-text">Create an account</a>
