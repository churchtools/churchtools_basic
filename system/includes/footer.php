
      
<!--  /div--><!-- /row -->

<hr>
  <footer>
    
    <p class="pull-right">
<?php if (!$embedded): ?>
  <?php if (user_access("administer persons", "churchcore")): ?>
      <a href="#" id="simulate_person" title="<?=t("simulate.user")?>"><img src="<?=CHURCHCORE?>/images/person_simulate.png" style="max-width:16px"/></a>&nbsp;
  <?php endif; ?>
  
  <?php if (userLoggedIn()): ?>
      <a href="#" id="email_admin" title="<?=t("write.email.to.admin")?>"><img src="<?=CHURCHCORE?>/images/email.png" style="max-width:16px"/></a>&nbsp;
      <a href="#" id="language_selector"><img src="<?=CHURCHCORE?>/images/flag_<?=$lang?>.png" style="max-width:16px"/></a>&nbsp;
  <?php else: ?>
  <?php //TODO: for which is a flag without function needed? ?>
      <img src="<?=CHURCHCORE?>/images/flag_<?=$lang?>.png" style="max-width:16px"/>&nbsp;
  <?php endif; ?>
    </p>

    <p>
  <?php if (getConf("cronjob_delay") > 0): ?>
      <img width="0" height="0" src="?q=cron&standby=true" />
  <?php endif; ?>
<?php endif; ?>
      <small>&copy; <a href="http://www.churchtools.de" target="_blank">www.churchtools.de</a> v<?=$config["version"]?></small>
    </p>

    </footer>
  </div>
<script src="<?=BOOTSTRAP?>/js/bootstrap.js"></script>
</body>
</html>
