<?php

class field_location extends SK_FormField
{
	public  $items = array();
	
	public  $labelsection;
	
	/**
	 * Constructor.
	 *
	 * @param string $name
	 */
	public function __construct( $name = 'location' ) {
		parent::__construct($name);
	}
	
	public function setup(SK_Form $form){
		
		$this->js_presentation['$parent_node']='{}';
		$this->js_presentation['$container_node']='{}';
		
		$this->js_presentation['fields']='[]';
		
				
		$this->js_presentation['$prototype_node']='{}';
		
		$this->js_presentation['construct']='
			function($input, formHandler, auto_id){
				handler = this;
				this.$errorContainer = $("#"+auto_id+"-container");
				this.$parent_node = $input.parents("tbody:eq(0)");
				this.$container_node = $input.parents(".location_container:eq(0)");
				if ($input.val()!=""){
				
					this.$parent_node.find("tr.state_id select").change(function(){
					    handler.$errorContainer.empty();
						handler.change_event($(this),$input.val());
					});
					
					this.$parent_node.find("input[type=button]").click(function(){
					    handler.$errorContainer.empty();
						handler.change_event(handler.$parent_node.find("tr.zip input[type=text]"),$input.val());
					});
					this.suggest(this.$parent_node.find("input[name*=city_name]"),this.$parent_node.find("tr.state_id select").val());
				}
				
				$input.change(function(){
				    handler.$errorContainer.empty();
					handler.change_event($input);
				});
			} 
		';
		
		$this->js_presentation['change_event']='
			function($input, param){
				var handler = this;
			
				handler.fields[$input.parents("tr:eq(0)").attr("class")] = $input.val();
				$.ajax({
							url: "'.URL_FIELD_RESPONDER.'",
							method: "post",
							dataType: "json",
							data: {action: "process_location", changed_item : $input.parents("tr:eq(0)").attr("class") , value : $input.val(), param : param},
							success: function(result){
							 	handler.ajax_receiver(result);
							}
						});
			} 
		';
		
		$this->js_presentation['ajax_receiver']='
			function(result){
				handler = this;
							
				$.each(result.hide_items,function(i,item){
					handler.$parent_node.find("tr."+item).css("display","none");
					handler.$parent_node.find("tr."+item+" td.value").empty();
				});
				
				$.each(result.assign,function(item,html){
					var $node = handler.$parent_node.find("tr."+item+" td.value");
					$node.empty();
					$node.append(html);
					
					
					switch(item){
					case "state_id":
						$node.find("select[name=state_id]").change(function(){
						    handler.$errorContainer.empty();
							handler.change_event($(this),handler.fields.country_id);
						});
						break;
					case "zip":
						$node.find("input[type=button]").click(function(){
						    handler.$errorContainer.empty();
							handler.change_event($node.find("input[type=text]"),handler.fields.country_id);
						});
						break;
						
					case "city_id":
						handler.suggest($node.find("input[name=city_name]"),handler.fields.state_id);
						break;
					}
					
					$node.find("input, select").each(function(){
						var name = $(this).attr("name");
						if (name!=undefined){
							$(this).attr("name","'.$this->getName().'["+name+"]");
						}
					});
				});
								
				$.each(result.show_items,function(i,item){
					//this.$parent_node.find("tr."+result.show_items[item]).css("display","table-row");
					handler.$parent_node.find("tr."+item).fadeIn("slow");
				});  
				
				
			}
			
		';
		
		$this->js_presentation['$'] = 'function($expr){
			return this.$container_node.find($expr);
		}';
		
		$this->js_presentation['$suggest_cont_prototype'] = 'undefined';
		
		$this->js_presentation['showSuggest'] = 'function($node, suggests){
			var handler = this;
			
			var removeSuggest = function(){
				$node.parent().siblings(".suggest_cont").remove();
			}
			
			if (this.$suggest_cont_prototype ==undefined) {
				this.$suggest_cont_prototype = this.$(".suggest_cont").remove().clone();
			}		
			
			var $suggest_cont = this.$suggest_cont_prototype.clone();
			
			removeSuggest();
			
			if (suggests.length <= 0) {
				return;
			}
			
			var itemHover = function($item){
				$item.parent().find(".suggest_item").removeClass("hover");
				$item.addClass("hover");
			}
			
			$.each(suggests, function(i, item){
			
				var $item_node = $suggest_cont.find(".prototype_node").clone().removeClass("prototype_node").css("display","block");
				var $parent_node = $suggest_cont.find(".prototype_node").parent();
				
				$item_node.html(item.suggest_label);
				
				$item_node.mouseover(function(){
					itemHover($item_node);
				});
				
				$item_node.click(function(){
					$node.val(item.name);
					removeSuggest();
					$node.focus();
				});
				
				$parent_node.append($item_node);
				
			});
			
			$node.unbind("keypress");
			$node.keypress(function(eventObject){
				
				$selected_item = $node.parent().find("div.suggest_cont ul li.hover");
				if ( $selected_item.length == 0 ) {
					$selected_item = $node.parent().find("div.suggest_cont ul li:visible:eq(0)");
					itemHover($selected_item);
					return;
				}
				
				switch(eventObject.keyCode){
					case 40:
						itemHover($selected_item.next(".suggest_item"));
						break;
					case 38:
						itemHover($selected_item.prev(".suggest_item"));
						break
					case 13:
						itemHover($selected_item.prev(".suggest_item"));
						if ($selected_item.length > 0) {
							$selected_item.click();
							return false;	
						}
						break
				}
			});
			
			$node.unbind("blur");
			$node.blur(function(){
				window.setTimeout(removeSuggest,200);
			});
						
			$node.parent().after($suggest_cont);
			$suggest_cont.css("width",$node.outerWidth()).show();
			
		}';
		
		$this->js_presentation['suggest'] = 'function($node, state_id){
			var handler = this;
			var timeout;
			var last_str;	
			
			
			$node.unbind();
			$node.keyup(function(eventObject){
				
				var $field = $(this);
				
				var getCityList = function(str, state_id) {
					if (!$.trim(str)) {
						last_str = "";
						handler.showSuggest($node, []);
						return;
					}	
				
					var key = eventObject.which;
					if ( last_str == str || key==13) {
						return;
					}
					
					var params ={
						str : str,
						state_id: state_id,
						action: "location_get_city"
					};
					$.ajax({
								url: "'.URL_FIELD_RESPONDER.'",
								method: "post",
								dataType: "json",
								data: params,
								success: function(result){
									last_str = str;
									handler.showSuggest($node, result, eventObject);
								}
						});
				}
				
				var suggestGetList = function(){
					var str = $field.val();
					getCityList(str, state_id);
				}
				
				if (timeout != undefined) {
					window.clearTimeout(timeout)
				}
				timeout = window.setTimeout(suggestGetList,300);
				return false;
			});
		}';
		
	}
	
	public static function ajax_handler($params)
	{
		$item = $params['changed_item'];
		$value = $params['value'];
		$parametr = $params['param'];
		$result = array();
		$show_items = array();
		$hide_items = array();
		$assign = array();
		
		
		switch ($item){
			case 'country_id':
				$hide_items = array('zip','state_id','city_id','custom_location','city_by_zip');
				if(isset($value) && strlen($value))
				{
					if (app_Location::isCountryZipExists($value)){
						$show_items[]='zip';
						

						if ( !app_Location::CountryCityExists($value))
						{
							$assign['zip'] = '<input type="text" name="zip" size="6">';
							$show_items[] = 'custom_location';
							$assign['custom_location'] = '<input type="text" name="custom_location">';
						} else {
							$assign['zip'] = '<input type="text" name="zip" size="8">&nbsp<input type="button" value="'.SK_Language::text("%forms._fields.location.detect_location").'">';
						}
					
					}
					elseif (app_Location::isCountryStateExists($value))
					{
						$staties = app_Location::getStaties($value);
						
						$out = '<select name="state_id">';
						$id = SK_ProfileFields::get('state_id')->profile_field_id;
						$out.='<option>'.SK_Language::text("%profile_fields.select_invite_msg.".$id).'</option>';
							foreach ($staties as $item){
								$out.='<option value="'.$item['Admin1_str_code'].'">'.$item['Admin1_str_name'].'</option>';
							}
						$out.= '</select>';
						
						$assign['state_id']=$out;
						$show_items[] = 'state_id';
					}
					else
					{
						$show_items[] = 'custom_location';
						$assign['custom_location'] = '<input type="text" name="custom_location">';
					}
				}
				
				break;
			
			case 'state_id':
				$hide_items = array('zip','city_id','custom_location','city_by_zip');
				if (isset($value) && strlen($value)) {
					if (app_Location::isStateCityExists($value))
					{
						$show_items[]='city_id';
						$assign['city_id'] = '<div><input type="text" autocomplete="off" name="city_name"></div>';
					}
					else {
						$show_items[] = 'custom_location';
						$assign['custom_location'] = '<input type="text" name="custom_location">';
					}
					
				}
				break;
				
			case 'zip':
				$hide_items = array('city_by_zip');
				if (isset($value) && strlen(trim($value))) {
					
					$city = app_Location::CitiesByZip($parametr,$value);
					$show_items[]='city_by_zip';
					if(count($city)>1){
						$assign['city_by_zip'] = '<select name="city_id">';	
						$assign['city_by_zip']='<select name="city_id">
							<option value="">'.SK_Language::text("%profile_fields.select_invite_msg.".'114').'</option>';
						foreach ($city as $item){
							$assign['city_by_zip'].='<option value="'.$item['city_id'].'">'.$item['city_name'].'</option>';	
						}
						
						$assign['city_by_zip'].= '</select>';
					}
					elseif(count($city)==1) {
						$assign['city_by_zip'] = $city[0]['city_name'] . '<input type="hidden" name="city_id" value="'.$city[0]['city_id'].'">';
					}
					else {
						$assign['city_by_zip'] = SK_Language::text("%forms._fields.location.not_detected");
					}
					
					
				}
				break;
		}
		
		return array(
					'show_items'=>$show_items,
					'hide_items'=>$hide_items,
					'assign'	=>$assign
					);
		
	}
	
	public function validate( $value )
	{
		
		/*printArr('country = '.$value['country_id']."\n");
		printArr( 'state_id = '.@$value['state_id']."\n");
		printArr( 'city_id = '.@$value['city_id']."\n");
		printArr( 'city_name = '.@$value['city_name']."\n");
		printArr( 'zip = '.@$value['zip']."\n");
		printArr( 'custom_location = '.@$value['custom_location']."\n");*/
		
		if (empty($value['country_id'])) {
			$this->profile_field_id = SK_ProfileFields::get('country_id')->profile_field_id;
			throw new SK_FormFieldValidationException('country');
		}
		
		if ( empty($value['state_id']) && !empty($value['zip']) ) {
			$value['state_id'] = app_Location::StateByZip($value['country_id'], @$value['zip']);
		}
		
		if ( !empty($value['city_name']) && empty($value['city_id']) ) {
			$value['city_id'] = app_Location::CityCodeByName($value['state_id'], $value['city_name']);
		}
		
		if (!empty($value['zip']) && !app_Location::isZipExists($value['country_id'],$value['zip'])) {
			$this->profile_field_id = SK_ProfileFields::get('zip')->profile_field_id;
			throw new SK_FormFieldValidationException('zip');
		}	
		
		if (empty($value['custom_location'])) {
	
			if (empty($value['state_id'])) {
				$this->profile_field_id = SK_ProfileFields::get('state_id')->profile_field_id;
				throw new SK_FormFieldValidationException('state');
			}
			
			if (empty($value['city_id'])) {
				$this->profile_field_id = SK_ProfileFields::get('city_id')->profile_field_id;
				throw new SK_FormFieldValidationException('city');
			}
		}
		
		
		return $value;
	}
	
	
	private function render_prepare()
	{
		$value = $this->getValue();
		
		$country_id = (isset($value['country_id']) && strlen($value['country_id'])) ? $value['country_id'] : false;
		$state_id = (isset($value['state_id']) && strlen($value['state_id'])) ? $value['state_id'] :false;
		
		$this->items['country_id']['display'] = true;
		if ($country_id) 
		{
			if (app_Location::isCountryZipExists( $country_id ) )
			{
				$this->items['zip']['display'] = true;
				$this->items['state_id']['display'] = false;
				$this->items['city_id']['display'] = false;
			}
			else 
			{
				$this->items['zip']['display'] = false;
				
				if ( $country_id && app_Location::isCountryStateExists( $country_id ) )
				{
					$this->items['state_id']['display'] = true;
												
					if ( $state_id && app_Location::isStateCityExists( $state_id ) ){
						$this->items['city_id']['display'] = true;
					}
					else{
						$this->items['custom_location']['display'] = true;
					}
				} else {
					
					$this->items['custom_location']['display'] = true;
				}
				
			}
			
			if ( ! app_Location::isCountryStateExists($country_id) ) {
				$this->items['custom_location']['display'] = true;
			}
		}
		
		
	}
	
	
	private function renderField($field)
	{
		$value = $this->getValue();
		
		$country_id = (isset($value['country_id']) && strlen($value['country_id'])) ? $value['country_id'] : null;
		$state_id = (isset($value['state_id']) && strlen($value['state_id'])) ? $value['state_id'] : null;
		$city_id = (isset($value['city_id']) && strlen($value['city_id'])) ? $value['city_id'] : null;
		
		$city_name = isset($value['city_id']) ? app_Location::CityNameById($value['city_id']) : null;
		
		$zip = (isset($value['zip']) && strlen($value['zip'])) ? $value['zip'] : null;
		$custom_location = (isset($value['custom_location']) && strlen($value['custom_location'])) ? $value['custom_location'] : null;
		
		$pr_field = SK_ProfileFields::get($field);
		
		$display = @$this->items[$field]['display'] ? '' : 'style="display:none"';
		$requiredStar = '<span class="required_star">*</span>';
		$output ='<tr class="'.$field.'" '.$display.'>
					<td class="label">'.SK_Language::text("$this->labelsection.".$pr_field->profile_field_id) . $requiredStar . '</td>
					<td class="value">';
		switch ($field)
		{
			case 'country_id':
				$countries = app_Location::Countries();
		
				$output.='<select name="'.$this->getName().'['.$field.']">
							<option value="">'.SK_Language::text("%profile_fields.select_invite_msg.".$pr_field->profile_field_id).'</option>';
				
				foreach ($countries as $country){
					$selected = ($country['Country_str_code'] == $country_id) ? 'selected' : '';
					$output.='<option value="'.$country['Country_str_code'].'" '.$selected.'>'.$country['Country_str_name'].'</option>';
				}
				
				$output.='</select>';
				break;	
				
			case 'state_id':
				if ($country_id && $display=='') {
					$staties = app_Location::getStaties($country_id);
					$staties = is_array($staties) ? $staties : array(); 
					
					$output.='<select name="'.$this->getName().'['.$field.']">
							<option value="">'.SK_Language::text("%profile_fields.select_invite_msg.".$pr_field->profile_field_id).'</option>';
					
					foreach ($staties as $state){
						$selected = ($state['Admin1_str_code'] == $state_id) ? 'selected' : '';
						$output.='<option value="'.$state['Admin1_str_code'].'" '.$selected.'>'.$state['Admin1_str_name'].'</option>';
					}
					$output.='</select>';
				}
				break;	
				
			case 'custom_location':
				if ($country_id && $display=='') {
					$output.='<input type="text" name="'.$this->getName().'[custom_location]" value="'.$custom_location.'">';
				}
				break;	
				
			case 'city_id':
				if ($country_id && $display=='') {
					$output.='<div><input type="text" autocomplete="off" name="'.$this->getName().'[city_name]" value="'.$city_name.'"></div>';
				}
				break;	
				
			case 'zip':
				
				
				if ($display=='') {
					
					if ($custom_location && !app_Location::CountryCityExists($country_id)) {
						$output.= '<input type="text" name="'.$this->getName().'[zip]" value="'.$zip.'"  size="8">';
						break;
					} else {
						$output.='<input type="text" name="'.$this->getName().'[zip]" size="8" value="'.$zip.'">&nbsp<input type="button" value="'.SK_Language::text("%forms._fields.location.detect_location").'">';
					}
				}
				
				$city_by_zip_display = (isset($zip) && strlen($zip) && app_Location::isZipExists($country_id,$zip)); 
				
				$output.='</td></tr>';
				
				
				$output.='<tr class="city_by_zip" '.($city_by_zip_display ? '' : 'style="display:none"').'>
							<td class="label">'.SK_Language::text("%profile_fields.label.".'114').'</td>
							<td class="value">';
				if (strlen($city_by_zip_display)){
					$cities = app_Location::CitiesByZip($country_id,$zip);
					if (count($cities)>1) {
						$output.='<select name="'.$this->getName().'[city_id]">
							<option value="">'.SK_Language::text("%profile_fields.select_invite_msg.".'114').'</option>';
						foreach ($cities as $item){
							$selected = ($item['city_id'] == $city_id) ? 'selected' : '';
							$output.='<option value="'.$item['city_id'].'" '.$selected.'>'.$item['city_name'].'</option>';
						}
						$output.='</select>';
					}
					elseif (count($cities)==1){
						$output.=$cities[0]['city_name'].'<input type="hidden" name="'.$this->getName().'[city_id]" value="'.$cities[0]["city_id"].'">'; 
					}
					else {
						$output.=SK_Language::text("%forms._fields.location.not_detected");
					}
				}
				break;	
			
		}
		$output.='</td></tr>';
		return $output;
	}
	
	
	
	
	public function render( array $params = null, SK_Form $form = null )
	{
		$value = $this->getValue();
				
		$this->labelsection = isset($params['labelsection'])?$params['labelsection'] : '%profile_fields.label';
		$this->render_prepare();	
		
		$fields = array('country_id','state_id','city_id','zip','custom_location');
		
		$output ='<div class="location_container"><table class="form"><tbody>';
		
		foreach ($fields as $item){
			$output.=$this->renderField($item);		
		}
		$output.= '</tbody></table>
			<div class="suggest_cont" style="display:none">
				<ul>
					<li class="suggest_item prototype_node" style="display:none"></li>
				</ul>
			</div>
		</div>';
		return $output;
	}
	
}