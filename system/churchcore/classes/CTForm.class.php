<?php
/**
 * contains CTForm and the only from this class used classes CC_Button, CC_Field, CC_HTMLElement
 * 
 */



/**
 * Html form
 * 
 *
 */
class CTForm {
  // TODO: use const or not needed?
  const INPUT_REQUIRED = 'INPUT_REQUIRED';
  const INPUT_OPTIONAL = 'INPUT_OPTIONAL';
  const TEXTAREA = 'TEXTAREA';
  const EMAIL = 'EMAIL';
  const CHECKBOX = 'CHECKBOX';
  const FILEUPLOAD = 'FILEUPLOAD';
  const PASSWORD = 'PASSWORD';
  
  public $fields = array ();
  private $buttons = array ();
  private $validator = null; //name of validate function
  private $name;
  private $header;
  private $subheader;
  private $help_url;
  public $fieldTypes = array (self::INPUT_REQUIRED, self::INPUT_OPTIONAL, self::TEXTAREA, self::EMAIL, self::PASSWORD, 
                              self::CHECKBOX, self::FILEUPLOAD);

  /**
   * Constructor
   * 
   * @param string $name          
   * @param string $validator; name of a user function to use for validation         
   * @param string $help_url; last part of help on churchtools.de           
   */
  public function __construct($name, $validator, $help_url = null) {
    $this->name = $name;
    $this->validator = $validator;
    $this->help_url = $help_url;
  }

  /**
   * add field to form, return $field object
   *
   * @param string $name          
   * @param string $class          
   * @param string $fieldType
   *          - one of this constants: self::INPUT_REQUIRED, self::INPUT_OPTIONAL, self::TEXTAREA,
   *          self::EMAIL, self::PASSWORD, self::CHECKBOX, self::FILEUPLOAD
   * @param string $label          
   * @param string $autofocus
   * 
   * @return added field for use of add()->setValue()
   */
  public function addField($name, $class, $fieldType, $label = "", $autofocus = false) {
    if (!in_array($fieldType, $this->fieldTypes)) echo ("There is no FieldTyp $fieldType!");
    
    $field = new CC_Field($this, $name, $class, $fieldType, $label, $autofocus);
    $this->fields[$name] = $field;
    
    return $this->fields[$name];
  }

  public function getName() {
    return $this->name;
  }

  /**
   * add button to form
   * 
   * @param string $label          
   * @param string $icon, css class, will be prefixed by 'icon-'          
   */
  public function addButton($label, $icon) {
    $this->buttons[] = new CC_Button("btn_" . count($this->buttons), $label, $icon);
  }

  /**
   * set form header 
   * @param string $big, the title
   * @param string $small, default = false the subtitle
   */
  public function setHeader($header, $subheader = false) {
    $this->header = $header;
    $this->subheader = $subheader;
  }

  /**
   * set validator
   * @param unknown $validator
   */
  public function setValidator($validator) {
    $this->validator = $validator;
  }

  /**
   * render form
   * 
   * TODO: if variable source doesn't matter, use REQUEST by removing POST from readVar()
   * @return string html content of form
   */
  public function render() {
    global $q_orig;
    
    // check if dada was sent
    if ($formData = readVar($this->getName(), false, $_POST)) {
      // reset all checkboxes
      foreach ($this->fields as $field) {
        if ($field->getFieldType() == "CHECKBOX") $field->setValue("off");
      }
      // set values
      foreach ($formData as $key => $val) {
        $this->fields[$key]->setValue($val);
      }
      //validate values
      $isValid = true;
      foreach ($this->fields as $field) {
        if (!$field->isValid()) $isValid = false;
      }
      if ($isValid) {
        if (!$this->validator || !is_callable($this->validator)) return "no or invalid validator given!"; 
        $ret = call_user_func($this->validator, $this);
        // if ($ret!=true)
        // return $ret;
      }
    }
    
    // TODO: maybe use template?
    // render form
    $txt = "";
    
    if ($this->header) $txt .= "<h1>$this->header</h1>";
    if ($this->subheader) $txt .= "<p>$this->subheader</p>";
    
    $txt .= '<div class="form">'.NL;
    $txt .= '<form class="well form-vertical" id="verticalForm" action="?q=' . $q_orig . '" method="post">'.NL;
    if ($this->help_url) {
      $txt .= '<label class="ct_help_label"><a title="' . t("getting.help") .
           '" href="http://intern.churchtools.de?q=help&doc=' . $this->help_url . '" target="_clean">';
      $txt .= '<i class="icon-question-sign"></i></a></label>'.NL;
    }
    
    $requiredFields = false;
    // render fields
    foreach ($this->fields as $field) {
      $txt .= $field->render();
      if ($field->isRequired()) $requiredFields = true;
    }
    foreach ($this->buttons as $button) {
      $txt .= $button->render() . "&nbsp;";
    }
    $txt .= '</form>';
    if ($requiredFields) $txt .= '<p class="note">' . t("fields.with.asterisk.has.to.be.filled") . '</p>'.NL;
    $txt .= '</div>'.NL;

    return $txt;
  }

}


/*************************************************************************************
 * 
 * Class for Html Element
 *
 *************************************************************************************/
class CC_HTMLElement {
  private $name;
  private $class; // css class
  protected $value = null;

  /**
   *
   * @param string $name          
   * @param string $class          
   */
  public function __construct($name, $class) {
    $this->name = $name;
    $this->class = $class;
  }

  public function __toString() {
    if (is_bool($this->value)) return ($this->value) ? "1" : "0";
    if (!is_string($this->value)) return "";
    return $this->getValue();
  }

  public function getName() {
    return $this->name;
  }

  public function getClass() {
    return $this->class;
  }

  /**
   * set value (does nothing else)
   * @param string or bool $val
   */
  public function setValue($val) {
    $this->value = $val;
  }

  public function getValue() {
    return $this->value;
  }

}


/*************************************************************************************
 * 
 * Class for Html form field
 *
 *************************************************************************************/
class CC_Field extends CC_HTMLElement {
  private $fieldType = "INPUT_OPTIONAL";
  private $label;
  private $form;
  private $error = null;

/**
 * 
 * @param CTForm $form; parent form of field
 * @param string $name
 * @param string $class, css
 * @param string $fieldType
 * @param string $label
 * @param string $autofocus
 */  
  public function __construct($form, $name, $class, $fieldType, $label, $autofocus = false) {
    parent::__construct($name, $class);
    $this->form = $form;
    $this->fieldType = $fieldType;
    $this->label = $label;
    $this->autofocus = $autofocus;
  }

  public function getLabel() {
    return $this->label;
  }

  public function isRequired() {
    return in_array($this->fieldType, array("INPUT_REQUIRED", "EMAIL", "PASSWORD"));
  }

  public function getFieldType() {
    return $this->fieldType;
  }
  
  
  /**
   * set Value
   * @param string or bool $val, for checkbox "on" or 1 is valid
   */
  public function setValue($val) {
    if ($this->fieldType == "CHECKBOX") $this->value = ($val == "on" || $val == 1 ? true : false);
    else $this->value = $val;
  }

  /**
   * check validity
   * 
   * // add some more tests, maybe use sanitize filters or use them in the calling code
   * 
   * @return boolean
   */
//   $args = array(
//       'username'      => array('filter' => FILTER_SANITIZE_STRING, 'flags'  => FILTER_FLAG_NO_ENCODE_QUOTES),
//       'user_password' => array('filter' => FILTER_SANITIZE_STRING, 'flags'  => FILTER_FLAG_NO_ENCODE_QUOTES),
//       'user_email' 	 => FILTER_SANITIZE_EMAIL,
//       'user_website'  => FILTER_SANITIZE_URL,
//   );
//   $formData = filter_input_array(INPUT_POST, $args);

  public function isValid() {
    if ($this->isRequired() && empty($this->value)) {
      $this->error = t("please.complete.this.field");
      return false;
    }
    else if ($this->fieldType == "EMAIL") {
      if (!strpos($this->value, '@')) {
        $this->error = t("please.enter.valid.email");
        return false;
      }
    }
    return true;
  }

  public function setError($txt) {
    $this->error = $txt;
  }

  /**
   * TODO: is it important to use getVar() with empty functions rather then $this->var which can be used inside " "?
   * @return string
   */
  public function render() {
    global $files_dir;
    $txt = "";
    if (in_array($this->fieldType, array("INPUT_OPTIONAL", "INPUT_REQUIRED", "EMAIL", "PASSWORD", "TEXTAREA")))
    {
      $txt .= '<label for="'. $this->form->getName().'_'.$this->getName().'">'.$this->getLabel();
      if ($this->isRequired()) $txt.=' <span class="required">*</span>';
      $txt .= '</label>'.NL;
      
      $txt .= '<div class="control-group '. $this->getClass(). ($this->error ? " error" : '').'">'.NL;
      
      if ($this->fieldType == "TEXTAREA") {
        $txt .= '<textarea rows=6 class="span8" name="'.$this->form->getName().'['.$this->getName().']" id="'.
             $this->form->getName().'_'.$this->getName().'">'. ($this->value ?  $this->value : '').'</textarea>'.NL;
      }
      else { // INPUT, EMAIL oder PASSWORT
        $txt .= '<input class="span3" name="'.$this->form->getName().'['.$this->getName().']" id="'.
                  $this->form->getName(). '_'. $this->getName(). '" ';
        $txt .= ($this->fieldType == "PASSWORD" ? 'type="password" ' : 'type="text" ').
                  ($this->value ? ' value="'. $this->value. '" ' : ''). ($this->autofocus ? ' autofocus="autofocus"/>' : '/>').NL;
      }
      if ($this->error) $txt .= '<span class="help-inline error">' . $this->error . '</span>';
      $txt .= '</div>'.NL;
    }
    else if ($this->fieldType == "CHECKBOX") {
      $txt .= '<label class="checkbox" for="'.$this->form->getName().'_'.$this->getName().'">';
      $txt .= '<input name="'.$this->form->getName().'['.$this->getName().']" id="'.$this->form->getName().'_'.$this->getName();
      $txt .= '" type="checkbox"'. ($this->value ? " checked />" : "/>"). $this->label. '</label>'.NL;
    }
    else if ($this->fieldType = "FILEUPLOAD") {
      $txt .= '<label class="" for="'.$this->form->getName().'_'.$this->getName().'"> ';
      $txt .= $this->label. '<span id="image_form">';
      if ($this->value) {
        $txt .= '&nbsp; <img style="max-width:100px;max-height:100px" src="'.$files_dir."/files/logo/".$this->value.'"/>'.
                '&nbsp; <a href="#" id="del_logo">l&ouml;schen</a>';
      }
      $txt .= '</span></label>'.NL.'<div id="upload_button">'. t('again.please'). '</div>'.NL;
      $txt .= '<input type="hidden" name="'. $this->form->getName(). '['.$this->getName().']" id="'.
                $this->form->getName().'_'.$this->getName().'" value="'.$this->value.'"/>'.NL;
      $txt .= '<script>
        jQuery(document).ready(function() {
          var uploader = new qq.FileUploader({
          element: document.getElementById("upload_button"),
          action: "?q=admin/uploadFile",
          params: {
            domain_type:"logo",
            resize:32
          },
          multiple:false,
          debug:true,
          onComplete: function(file, response, res) {
            if (res.success) {
              $("#image_form").html("<img src=\""+settings.files_url+"/files/logo/"+res.filename+"\"/>");
              $("#AdminForm_site_logo").val(res.filename);
            }
          }
        });
        $("#del_logo").click(function() {
          if (confirm("Wirklich Datei entfernen?")) {
            churchInterface.setModulename("admin");
            churchInterface.jsendWrite({func:"saveLogo", filename:null});
            window.location.reload();
          }
        });
       });
      </script>';
    }
    else
      return NL.NL."FieldType $this->fieldType not implemented!".NL.NL; // TODO: error handling?
    
    return $txt;
  }

}


/**************************************************************************************
 * 
 * Class for Html form button
 *
 *************************************************************************************/
class CC_Button extends CC_HTMLElement {
  private $label;
  private $icon;

  /**
   * 
   * @param string $name
   * @param string $label
   * @param string $icon
   */
  public function __construct($name, $label, $icon) {
    parent::__construct($name, "btn");
    $this->label = $label;
    $this->icon = $icon;
  }

  /**
   * render button
   * @return string
   */
  public function render() {
    return '<button class="'. $this->getClass(). '" type="submit" name="'. $this->getName(). '">'. '
            <i class="icon-$this->icon"></i>'. $this->label. '</button>'.NL;
  }

}
