<?php

/*
=====================================================
 This ExpressionEngine fieldtype was created by Laisvunas
 - http://devot-ee.com/developers/ee/laisvunas/
=====================================================
 Copyright (c) Laisvunas
=====================================================
 This is commercial Software.
 One purchased license permits the use this Software on the SINGLE website.
 Unless you have been granted prior, written consent from Laisvunas, you may not:
 * Reproduce, distribute, or transfer the Software, or portions thereof, to any third party
 * Sell, rent, lease, assign, or sublet the Software or portions thereof
 * Grant rights to any other person
=====================================================
 Purpose: A dropdown fieldtype displaying list of page types; 
 selecting different page type shows/hides relevant fields. 
=====================================================
global $DB, $DSP, $LANG;
*/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Page_type_switcher_ft extends EE_Fieldtype {

  var $info = array(
  		'name'     => 'Page Type Switcher',
  		'version'  => '1.2',
	 );
  
  //------------------------------------
 	//   Installer
 	//------------------------------------
  
  function install()
  {
    $global_settings = array();
    $global_settings['page_types_max_num'] = 7;
    return $global_settings;
  }
  // END FUNCTION
  
  //------------------------------------
 	//   Site settings form
 	//------------------------------------
  
  function display_global_settings()
  {
    $settings = array_merge($this->settings, $_POST);
    
    $site_url = rtrim($this->EE->config->item('site_url'), '/');
    $third_party_path_array = explode('/', trim(PATH_THIRD, '/'));
    $uri_segments_array = array();
    $uri_segments_array = array_slice($third_party_path_array, -3);
    //print_r($uri_segments_array);
    $third_party_uri = implode('/', $uri_segments_array);
    // Styles
    $this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$site_url.'/'.$third_party_uri.'/page_type_switcher/styles/display_global_settings.css" />');
    
    $this->EE->lang->loadfile('page_type_switcher');
    
    //print_r($this->settings);
    
    // Get custom field info 
    $query_field_info = $this->_get_field_info();
    $query_field_info_num_rows = $query_field_info->num_rows();
    //echo '$query_field_info_num_rows: ['.$query_field_info_num_rows.']<br><br>'.PHP_EOL;
    $query_field_info_result = $query_field_info->result_array();
    //print_r($query_field_info_result);
    
    // Find page type switcher's ftype_id
    $sql_ftype_id = "SELECT fieldtype_id 
                     FROM exp_fieldtypes 
                     WHERE name = 'page_type_switcher'
                     LIMIT 1";
    $query_ftype_id = $this->EE->db->query($sql_ftype_id);
    if ($query_ftype_id->num_rows() == 1)
    {
      $ftype_id = $query_ftype_id->row('fieldtype_id');
      //echo '$ftype_id: ['.$ftype_id.']<br><br>'.PHP_EOL;
    }
    
    // Create fieldtype's site settings form
    $r = '';
    $r .= '<table class="mainTable padTable" cellspacing="0" cellpaddind="0" border="0">'.PHP_EOL;
    $r .= '<tr>'.PHP_EOL;
    $r .= '<td width="30%">'.PHP_EOL;
    $r .= $this->EE->lang->line('page_types_max_num');
    $r .= '</td>'.PHP_EOL;
    $r .= '<td>'.PHP_EOL;
    $r .= '<input type="text" name="page_types_max_num" value="'.$this->settings['page_types_max_num'].'" style="width: 90%;" />';
    $r .= '</td>'.PHP_EOL;
    $r .= '</tr>'.PHP_EOL;
    
    if ($query_field_info_num_rows == 0)
    {
      $r .= '<script type="text/javascript">alert("'.$this->EE->lang->line('no_page_type_switchers_in_field_groups_allert').'");</script>'.PHP_EOL;
    }

    for ($i = 0; $i < $query_field_info_num_rows; $i++)
    {
      if ($i == 0 OR $query_field_info_result[$i]['group_id'] != $query_field_info_result[$i - 1]['group_id'])
      {
        $r .= '<tr class="header">'.PHP_EOL;
        $r .= '<th colspan="2">';
        $r .= $query_field_info_result[$i]['group_name'];
        $r .= '</th>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;
        
        for ($j = 1; $j <= $this->settings['page_types_max_num']; $j++)
        {
          $r .= '<tr>'.PHP_EOL;
          $r .= '<td width="30%">'.PHP_EOL;
          $r .= str_replace('{number}', $j, $this->EE->lang->line('page_type_number_name'));
          $r .= '</td>'.PHP_EOL;
          $r .= '<td>'.PHP_EOL;
          $value = '';
          if (isset($this->settings['field_group'][$query_field_info_result[$i]['group_id']]['page_type_'.$j.'_name']))
          {
            $value = $this->settings['field_group'][$query_field_info_result[$i]['group_id']]['page_type_'.$j.'_name'];
          }
          $r .= '<input type="text" name="'.'field_group['.$query_field_info_result[$i]['group_id'].'][page_type_'.$j.'_name]'.'" value="'.$value.'" style="width: 90%;">'; 
          $r .= '</td>'.PHP_EOL;
          $r .= '</tr>'.PHP_EOL;
        }
      }
      
      // Display all fields except page type switcher
      if ($query_field_info_result[$i]['field_type'] != 'page_type_switcher')
      {
        $r .= '<tr>'.PHP_EOL;
        $r .= '<td width="30%">'.PHP_EOL;
        $r .= $query_field_info_result[$i]['field_label'];
        $r .= '</td>'.PHP_EOL;
        $r .= '<td style="white-space: pre">'.PHP_EOL;
        for ($j = 1; $j <= $this->settings['page_types_max_num']; $j++)
        {
          $checked = '';
          if (isset($this->settings['page_types_field_id'][$query_field_info_result[$i]['field_id']]['page_type_'.$j]) AND $this->settings['page_types_field_id'][$query_field_info_result[$i]['field_id']]['page_type_'.$j] == 'y')
          {
            $checked = 'checked="checked"';
          }
          $r .= str_replace('{number}', $j, $this->EE->lang->line('page_type_number_checkbox')).NBS.NBS.'<input type="checkbox" name="'.'page_types_field_id['.$query_field_info_result[$i]['field_id'].'][page_type_'.$j.']'.'" value="y" '.$checked.'>'.NBS.NBS.NBS.NBS;
          
        }
        $r .= '</td>'.PHP_EOL;
        $r .= '</tr>'.PHP_EOL;
      }
    }
    $r .= '</table>'.PHP_EOL;

    return $r;
    
  }
  // END FUNCTION
  
  function save_global_settings()
 	{
    $this->settings = array_merge($this->settings, $_POST);
    $this->settings['page_types_max_num'] = is_numeric(trim($this->settings['page_types_max_num'])) ? trim($this->settings['page_types_max_num']) : 7;
    return $this->settings;
 	}
  // END FUNCTION
  
  //------------------------------------
 	//   Custom stuff
 	//------------------------------------
  
  function display_field($field_data = '')
  {   
    $this->EE->lang->loadfile('page_type_switcher');
    
    //print_r($this->settings);
    //echo '--------------------------------------'.PHP_EOL;
    //echo '$field_data: ['.$field_data.']<br><br>'.PHP_EOL;
    
    $site_url = rtrim($this->EE->config->item('site_url'), '/');
    $third_party_path_array = explode('/', trim(PATH_THIRD, '/'));
    $uri_segments_array = array();
    $uri_segments_array = array_slice($third_party_path_array, -3);
    //print_r($uri_segments_array);
    $third_party_uri = implode('/', $uri_segments_array);
    // Styles
    $this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$site_url.'/'.$third_party_uri.'/page_type_switcher/styles/display_field.css" />');
    
    $field_name = $this->field_name;
    //echo '$field_name: ['.$field_name.']<br><br>'.PHP_EOL;
    
    $field_id = end(explode('_', $field_name));
    if (isset($this->EE->safecracker->custom_fields[$field_name]))
    {
      $field_id = @$this->EE->safecracker->custom_fields[$field_name]['field_id'];
      $field_name = 'field_id_'.$field_id;
    }
    //echo '$field_id: ['.$field_id.']<br><br>'.PHP_EOL;
    
    $sql_group_id = "SELECT exp_channel_fields.group_id, exp_channel_fields.field_label  
                     FROM exp_channel_fields 
                     WHERE exp_channel_fields.field_id = '".$field_id."' AND exp_channel_fields.site_id = '".$this->EE->config->item('site_id')."' 
                     LIMIT 1";
    $query_group_id = $this->EE->db->query($sql_group_id);
    
    $group_id = $query_group_id->row('group_id');
    //echo '$group_id: ['.$group_id.']<br><br>'.PHP_EOL;
    $field_title = $query_group_id->row('field_label');
    
    $sql_custom_field_ids = "SELECT field_id 
                             FROM exp_channel_fields 
                             WHERE group_id = '".$group_id."' AND site_id = '".$this->EE->config->item('site_id')."' ";
    $query_custom_field_ids = $this->EE->db->query($sql_custom_field_ids);
    $query_custom_field_ids_result = $query_custom_field_ids->result_array();
    
    $custom_field_ids_array = array();
    foreach ($query_custom_field_ids_result as $row)
    {
      array_push($custom_field_ids_array, $row['field_id']);
    }
    $custom_field_ids_num = count($custom_field_ids_array);
    //print_r($custom_field_ids_array);
    
    $page_types_defined = FALSE;
    $page_types_array = array();
    $page_type_names_array = array();
    for ($j = 1; $j <= $this->settings['page_types_max_num']; $j++)
    {
      $page_types_array['page_type_'.$j.'_field_ids'] = array();
      $page_type_names_array[$j] = '';
      if (isset($this->settings['field_group'][$group_id]['page_type_'.$j.'_name']) AND $this->settings['field_group'][$group_id]['page_type_'.$j.'_name'])
      {
        $page_types_defined = TRUE;
        $page_type_names_array[$j] = $this->settings['field_group'][$group_id]['page_type_'.$j.'_name'];
        //echo '$page_type_names_array[$j]: '.$page_type_names_array[$j].PHP_EOL;
        for ($k = 0; $k < $custom_field_ids_num; $k++)
        {
          if (isset($this->settings['page_types_field_id'][$custom_field_ids_array[$k]]['page_type_'.$j]) AND $this->settings['page_types_field_id'][$custom_field_ids_array[$k]]['page_type_'.$j] == 'y')
          {
            array_push($page_types_array['page_type_'.$j.'_field_ids'], $custom_field_ids_array[$k]);
          }
        }
      }
      //print_r($page_types_array['page_type_'.$j.'_field_ids']);
    }
    
    $page_type_switcher_field = '';
    if ($page_types_defined == FALSE)
    {
      $page_type_switcher_field .= '<script type="text/javascript">alert("'.$this->EE->lang->line('no_page_types_defined_alert').$field_title.'");</script>';
    }
    
    if ($page_types_defined == TRUE)
    {
      $page_type_switcher_field .= '<select id="'.$field_name.'" name="'.$field_name.'" class="select">'.PHP_EOL;
      $page_type_switcher_field .= '<option value="">--</option>'.PHP_EOL;
      
      for ($j = 1; $j <= $this->settings['page_types_max_num']; $j++)
      {
        if (isset($this->settings['field_group'][$group_id]['page_type_'.$j.'_name']) AND trim($this->settings['field_group'][$group_id]['page_type_'.$j.'_name']))
        {
          $selected = '';
          if (trim($this->settings['field_group'][$group_id]['page_type_'.$j.'_name']) == $field_data)
          {
            $selected = 'selected="selected"';
          }
          $page_type_switcher_field .= '<option value="'.trim($this->settings['field_group'][$group_id]['page_type_'.$j.'_name']).'" '.$selected.'>'.trim($this->settings['field_group'][$group_id]['page_type_'.$j.'_name']).'</option>'.PHP_EOL;
        }
      }
      
      $page_type_switcher_field .= '</select>'.PHP_EOL;
      $page_type_switcher_field .= $this->_js($field_id, $page_types_array, $page_type_names_array);
    }
    
    return $page_type_switcher_field;
  }
  // END FUNCTION
  
  function save_field($field_data, $field_settings)
  {
    return $field_data;
  }
  // END FUNCTION
  
  function _get_field_info()
  {    
    // Get field groups which contain Page Type Switchers
    $sql_field_group_ids = "SELECT exp_field_groups.group_id
                              FROM
                                exp_field_groups
                                  INNER JOIN 
                                exp_channel_fields
                                  ON 
                                exp_field_groups.group_id = exp_channel_fields.group_id
                              WHERE exp_channel_fields.site_id = '".$this->EE->config->item('site_id')."' AND exp_channel_fields.field_type = 'page_type_switcher' ";
    
    $sql_field_info = "SELECT exp_field_groups.group_name, exp_field_groups.group_id, exp_channel_fields.field_id, exp_channel_fields.field_label, exp_channel_fields.field_order, exp_channel_fields.field_type
                       FROM 
                         exp_field_groups
                           INNER JOIN 
                         exp_channel_fields
                           ON 
                         exp_field_groups.group_id = exp_channel_fields.group_id 
                       WHERE exp_channel_fields.site_id = '".$this->EE->config->item('site_id')."' AND exp_field_groups.group_id IN (".$sql_field_group_ids.") 
                       ORDER BY exp_field_groups.group_name, exp_channel_fields.field_order ASC ";
    $query_field_info = $this->EE->db->query($sql_field_info);
    
    return $query_field_info;
  }
  // END FUNCTION
  
  function _js($field_id, $page_types_array, $page_type_names_array)
  {
    ob_start(); 
?>

<script type="text/javascript">
//<![CDATA[

//=================================================
//
// script pageTypeSwitcher
//
//=================================================

var pageTypeSwitcher = {
  
  publish_rows: [],

<?php  
for ($j = 1; $j <= $this->settings['page_types_max_num']; $j++)
{
  $field_ids_string = implode('|', $page_types_array['page_type_'.$j.'_field_ids']);
  $page_type_name = $page_type_names_array[$j];
?> 
  page_type_<?= $j ?>_name: '<?= $page_type_name ?>',
  page_type_<?= $j ?>_field_ids: '<?= $field_ids_string ?>',  
<?php  
}
?>  

  // Helper functions
  
  addEvent: function(elm, evType, fn, useCapture) { 
   	if(elm.addEventListener){
    		elm.addEventListener(evType, fn, useCapture);
    		return true;
    }
    else if(elm.attachEvent){
    		var r = elm.attachEvent("on" + evType, fn);
    		return r;
    }
    else {
    		elm["on" + evType] = fn;
    }
  },
  
  getElementsByClass: function(className, tag, elm) {
    if (document.getElementsByClassName) {
   			elm = elm || document;
   			var elements = elm.getElementsByClassName(className),
   				nodeName = (tag)? new RegExp("\\b" + tag + "\\b", "i") : null,
   				returnElements = [],
   				current;
   			for(var i=0, il=elements.length; i<il; i+=1){
   				current = elements[i];
   				if(!nodeName || nodeName.test(current.nodeName)) {
   					returnElements.push(current);
   				}
   			}
   			return returnElements;
   	}
   	else if (document.evaluate) {
   			tag = tag || "*";
   			elm = elm || document;
   			var classes = className.split(" "),
   				classesToCheck = "",
   				xhtmlNamespace = "http://www.w3.org/1999/xhtml",
   				namespaceResolver = (document.documentElement.namespaceURI === xhtmlNamespace)? xhtmlNamespace : null,
   				returnElements = [],
   				elements,
   				node;
   			for(var j=0, jl=classes.length; j<jl; j+=1){
   				classesToCheck += "[contains(concat(\' \', @class, \' \'), \' " + classes[j] + " \')]";
   			}
   			try	{
   				elements = document.evaluate(".//" + tag + classesToCheck, elm, namespaceResolver, 0, null);
   			}
   			catch (e) {
   				elements = document.evaluate(".//" + tag + classesToCheck, elm, null, 0, null);
   			}
   			while ((node = elements.iterateNext())) {
   				returnElements.push(node);
   			}
   			return returnElements;
   	}
   	else {
   			tag = tag || "*";
   			elm = elm || document;
   			var classes = className.split(" "),
   				classesToCheck = [],
   				elements = (tag === "*" && elm.all)? elm.all : elm.getElementsByTagName(tag),
   				current,
   				returnElements = [],
   				match;
   			for(var k=0, kl=classes.length; k<kl; k+=1){
   				classesToCheck.push(new RegExp("(^|\\s)" + classes[k] + "(\\s|$)"));
   			}
   			for(var l=0, ll=elements.length; l<ll; l+=1){
   				current = elements[l];
   				match = false;
   				for(var m=0, ml=classesToCheck.length; m<ml; m+=1){
   					match = classesToCheck[m].test(current.className);
   					if (!match) {
   						break;
   					}
   				}
   				if (match) {
   					returnElements.push(current);
   				}
   			}
   			return returnElements;
   	}
  },
  
  // Custom stuff
  
  switchPageType: function() {
    var page_type_field_ids;
    var selectbox_id;
    var selectbox;
    var field_pane_on;
    
    selectbox_id = 'field_id_<?= $field_id ?>';
    selectbox = document.getElementById(selectbox_id);
    //alert('selectbox.value: ' + selectbox.value);
    
    // Hide all publish rows except that containing page type switcher selectbox
    //alert('pageTypeSwitcher.publish_rows: ' + pageTypeSwitcher.publish_rows);
    for (var i = 0; i < pageTypeSwitcher.publish_rows.length; i++) {
      pageTypeSwitcher.publish_rows[i].style.display = 'none';
    }
    // Display publish rows according to selected page type                       
    for (var i = 0; i < <?= $this->settings['page_types_max_num'] ?>; i++) {
      if (pageTypeSwitcher['page_type_' + i + '_name'] && pageTypeSwitcher['page_type_' + i + '_name'] == selectbox.value) {
        page_type_field_ids = pageTypeSwitcher['page_type_' + i + '_field_ids'].split('|');
        //alert('page_type_field_ids: ' + page_type_field_ids);
        for (var j = 0; j < page_type_field_ids.length; j++) {
          if (page_type_field_ids[j]) {
            field_pane_on = document.getElementById('hold_field_' + page_type_field_ids[j]);
            if (field_pane_on)
            {
              field_pane_on.style.display = 'block';
            }
          }
        }
        break;
      }
    }
  }

}

pageTypeSwitcher.addEvent(window, 
                          'load', 
                          function() {
                            var selectbox_id;
                            var selectbox;
                            var field_pane_on_id;
                            var field_pane_on;
                            var publish_rows_all;
                            var needed_publish_row;
                            var publish_tab;
                            
                            // Attach event to page type switcher selectbox
                            selectbox_id = 'field_id_<?= $field_id ?>';
                            selectbox = document.getElementById(selectbox_id);
                            if (selectbox) {
                              pageTypeSwitcher.addEvent(selectbox, 'change', pageTypeSwitcher.switchPageType, false);
                            }
                            
                            // Display page type switcher selectbox
                            field_pane_on_id = 'hold_field_<?= $field_id ?>';
                            field_pane_on = document.getElementById(field_pane_on_id);
                            if (field_pane_on) {
                              field_pane_on.style.display = 'block';
                            }
                            
                            // Fetch publish rows except that containing page type switcher selectbox 
                            needed_publish_row = document.getElementById('hold_field_<?= $field_id ?>');
                            //needed_publish_row.style.border="1px solid red";
                            publish_tab = document.getElementById('publish');
                            publish_rows_all = pageTypeSwitcher.getElementsByClass('publish_field', '', publish_tab);
                            //alert('publish_rows_all.length: ' + publish_rows_all.length);
                            for (var i = 0; i < publish_rows_all.length; i++) {
                              if (publish_rows_all[i] != needed_publish_row && publish_rows_all[i] != document.getElementById('hold_field_title') && publish_rows_all[i] != document.getElementById('hold_field_url_title') && publish_rows_all[i].parentNode == publish_tab)  {
                                pageTypeSwitcher.publish_rows.push(publish_rows_all[i]);
                              }
                            }
                            
                            // Display publish rows according to selected page type
                            pageTypeSwitcher.switchPageType(); 

                          }, 
                          false);

//]]>
</script>

<?php
    $js = ob_get_contents();
    	
    ob_end_clean(); 
    
    return $js;  
  }
  // END FUNCTION
}
// END CLASS
?>