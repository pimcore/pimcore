<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables\GlobalVariables $app
 */

$this->extend('layout.html.php');
?>

<div class="page-header">
    <h1>Please log in to continue</h1>
</div>

<?php if ($this->error): ?>
    <div class="alert alert-danger"><?php echo $this->error->getMessage() ?></div>
<?php endif ?>

<form action="<?php echo $this->path('demo_login') ?>" method="post">
    <div class="form-group">
        <label for="login-username">Username:</label>
        <input type="text" id="login-username" class="form-control" name="_username" value="<?php echo $this->lastUsername ?>" placeholder="Username" />
    </div>

    <div class="form-group">
        <label for="login-password">Password</label>
        <input type="password" id="login-password" class="form-control" name="_password" placeholder="Password" />
    </div>

    <button type="submit" class="btn btn-primary pull-right">Login</button>
</form>
