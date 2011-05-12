<!DOCTYPE html>
<html>
<head>
  <?php HtmlHelper::javascript_include_tag("application")?>
  <?php HtmlHelper::css_link_tag("global")?>
  <meta charset="utf8">
  <title><?php echo SITE_NAME; ?></title>
</head>
<body>
<div id="header">
    <div id="user_info"><?php if($G["uid"]==0){ echo HtmlHelper::link_to(i18n("login"),HtmlHelper::path(array("login")))."&nbsp;".HtmlHelper::link_to(i18n("register"),HtmlHelper::path(array("register")));}else{ echo HtmlHelper::link_to(i18n("logout"),"logout");  } ?></div>
</div>

<?php $this->yield() ?>

</body>
</html>
