
      
<!--  /div--><!-- /row -->

<hr>
  <footer>
    
    <p class="pull-right">
<? if (!$embedded): ?>
  <? if (user_access("administer persons", "churchcore")):?> 
      <a href="#" id="simulate_person" title="<?=t("simulate.person")?>"><img src="<?=CHURCHCORE?>/images/person_simulate.png" style="max-width:16px"/></a>&nbsp;
  <? endif; ?>
  
  <? if (userLoggedIn()) :?> 
      <a href="#" id="email_admin" title="<?=t("send.email.to.admin")?>"><img src="<?=CHURCHCORE?>/images/email.png" style="max-width:16px"/></a>&nbsp;
      <a href="#" id="language_selector"><img src="<?=CHURCHCORE?>/images/flag_<?=$lang?>.png" style="max-width:16px"/></a>&nbsp;
  <? else: ?>
  <? //TODO: for which is a flag without function needed? ?>
      <img src="<?=CHURCHCORE?>/images/flag_<?=$lang?>.png" style="max-width:16px"/>&nbsp;
  <? endif; ?>
    </p>

    <p>
  <?if (readConf("cronjob_delay") > 0):?>
      <img src="?q=cron&standby=true"/> 
  <? endif; ?>
<? endif; ?>
      <small>&copy; <a href="http://www.churchtools.de" target="_blank">www.churchtools.de</a>v<?=$config["version"]?></small>
    </p>

    </footer>
  </div>
<script src="<?=BOOTSTRAP?>/js/bootstrap.js"></script>
</body>
</html>
