<?php
/*** COPYRIGHT NOTICE *********************************************************
 *
 * Copyright 2009-2017 ProjeQtOr - Pascal BERNARD - support@projeqtor.org
 * Contributors : -
 *
 * This file is part of ProjeQtOr.
 *
 * ProjeQtOr is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * ProjeQtOr is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for
 * more details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * ProjeQtOr. If not, see <http://www.gnu.org/licenses/>.
 *
 * You can get complete code of ProjeQtOr, other resource, help and information
 * about contributors at http://www.projeqtor.org
 *
 *** DO NOT REMOVE THIS NOTICE ************************************************/

/* ============================================================================
 * Menu defines list of items to present to users.
 */

require_once('_securityCheck.php');

class EmailTemplate extends SqlElement {
    
    public $_sec_description;
    public $id;
    public $name;
    public $idMailable;
    public $idType;
    public $title;
    public $idle;
    public $template;
    public $_sec_void;
    
    
    private static $_fieldsAttributes=array("idMailable"=>"",
        "idType"=>"nocombo",
        "name"=>"required",
        "template"=>"required"
    );  
    private static $_colCaptionTransposition = array(
        'idType' => 'type'
    );
    private static $_layout='
    <th field="id" formatter="numericFormatter" width="5%" ># ${id}</th>
    <th field="name" width="60%" >${name}</th>
    <th field="nameMailable" width="15%" formatter="nameFormatter">${idMailable}</th>
    <th field="nameType" width="15%" formatter="nameFormatter">${type}</th>
    <th field="idle" width="5%" formatter="booleanFormatter" >${idle}</th>
    ';
    
    
    function __construct($id = NULL, $withoutDependentObjects=false) {
        parent::__construct($id,$withoutDependentObjects);
    }
    
    
    /** ==========================================================================
     * Destructor
     * @return void
     */
    function __destruct() {
        parent::__destruct();
    }
    
    // ============================================================================**********
    // GET STATIC DATA FUNCTIONS
    // ============================================================================**********
    
    /** ==========================================================================
     * Return the specific layout
     * @return the layout
     */
    protected function getStaticLayout() {
      return self::$_layout;
    }
    /** ============================================================================
     * Return the specific colCaptionTransposition
     * @return the colCaptionTransposition
     */
    protected function getStaticColCaptionTransposition($fld=null) {
      return self::$_colCaptionTransposition;
    }
    
    protected function getStaticFieldsAttributes() {
      return self::$_fieldsAttributes;
    }
    
    public function getValidationScript($colName) {
      
      $colScript = parent::getValidationScript($colName);
   
      if ($colName=='idMailable') {
        $colScript .= '<script type="dojo/connect" event="onChange" args="evt">';
        $colScript .= '  dijit.byId("idType").set("value",null);';
        $colScript .= '  refreshList("idType","scope", mailableArray[this.value]);';
        $colScript .= '</script>';
      }
      return $colScript;
    }
}