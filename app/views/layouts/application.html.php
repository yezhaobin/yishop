<!DOCTYPE html>
<html>
<head>
  <?php HtmlHelper::javascript_include_tag("application")?>
  <?php HtmlHelper::css_link_tag("global")?>
  <title><?php echo SITE_NAME; ?></title>
</head>
<body>

<?php $this->yield() ?>

</body>
</html>
