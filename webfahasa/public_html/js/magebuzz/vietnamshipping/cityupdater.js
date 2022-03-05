CityUpdater = Class.create();
CityUpdater.prototype = {
	initialize: function(countryEl, regionEl, regionTextEl, cityTextEl, citySelectEl, wardfield, wardTextEl, wardSelectEl, postcodefield, postcodeTextbox, cities, wards) {
		this.regionEl = $(regionEl);
                this.regionTextEl = $(regionTextEl);
		this.cityTextEl = $(cityTextEl);
		this.citySelectEl = $(citySelectEl);
		this.wardfield = $(wardfield);
		this.wardTextEl = $(wardTextEl);
		this.wardSelectEl = $(wardSelectEl);
		this.postcodefield = $(postcodefield);
		this.postcodeTextbox = $(postcodeTextbox);
		this.cities = cities;
		this.wards = wards;
		this.countryEl = $(countryEl);
		this.city_first_time = true;
		this.ward_first_time = true;
		//if (this.citySelectEl.options.length<=1) {
			this.update();
	//	}
		if(this.countryEl.value == 'VN'){
		    this.showWardField();
		}
		else{
		    this.hideWardField();
		    this.hideCitySelect();
		    this.hideWardSelect();
		}

		Event.observe(this.regionEl, 'change', this.update.bind(this));
		Event.observe(this.countryEl, 'change', this.updateCountry.bind(this));
		Event.observe(this.citySelectEl, 'change', this.updateCity.bind(this));
		Event.observe(this.wardSelectEl, 'change', this.updateWard.bind(this));
	},
	
	update: function() {
		if (this.cities[this.regionEl.value] && this.regionTextEl.getStyle('display') === 'none') {
			var i, option, city, def;
			if(this.city_first_time){
			    def = this.citySelectEl.getAttribute('defaultValue');
			    if (def) {
				this.cityTextEl.value = def;
			    }
			}
			this.citySelectEl.options.length = 1;
			for (cityId in this.cities[this.regionEl.value]) {
				city = this.cities[this.regionEl.value][cityId];

				option = document.createElement('OPTION');
				option.value = city.name;
				option.text = city.name.stripTags();
				option.title = city.name;
				if(this.city_first_time){
				    if (def) {
					if(def == city.name){
					    option.selected = true;
					}
				    }
				}
				if (this.citySelectEl.options.add) {
					this.citySelectEl.options.add(option);
				} else {
					this.citySelectEl.appendChild(option);
				}
			}
			
			if(this.citySelectEl.options.length > 1){
			    this.showCitySelect();
			}
			else{
			    this.hideCitySelect(def);
			}
			if(this.city_first_time){
			    this.city_first_time = false;
			}
			this.updateCity();
		}
		else {
			this.hideCitySelect();
			this.hideWardSelect();
		}
	    
	}, 
	updateCountry: function(){
	    if(this.countryEl.value == 'VN'){
		this.showWardField();
		this.update();
	    }
	    else{
		this.hideWardField();
		this.hideCitySelect();
		this.hideWardSelect();
	    }
	},
	
	updateCity: function() {
	    var sIndex = this.citySelectEl.selectedIndex;
	    this.cityTextEl.value = this.citySelectEl.options[sIndex].value;

	    var city_code = "";
	    for (cityId in this.cities[this.regionEl.value]) {
		    city = this.cities[this.regionEl.value][cityId];
		    if(city.name == this.citySelectEl.value){
			city_code = city.code;
			break;
		    }
	    }
	    
	    if (this.wards[city_code] && this.cityTextEl.getStyle('display') === 'none') {
		    var i, option, ward, def;
		    if(this.ward_first_time){
			def = this.wardSelectEl.getAttribute('defaultValue');
		    }
		    this.wardSelectEl.options.length = 1;
		    for (wardId in this.wards[city_code]) {
			    ward = this.wards[city_code][wardId];
			    option = document.createElement('OPTION');
			    option.value = ward.name;
			    option.text = ward.name.stripTags();
			    option.title = ward.name;
			    if(this.ward_first_time){
				if (def) {
				    if(def == ward.name){
					option.selected = true;
				    }
				}
			    }
			    if (this.wardSelectEl.options.add) {
				    this.wardSelectEl.options.add(option);
			    } else {
				    this.wardSelectEl.appendChild(option);
			    }
		    }
		    if(this.wardSelectEl.options.length > 1){
			this.showWardSelect();
		    }
		    else{
			this.hideWardSelect();
		    }
		    if(this.ward_first_time){
			if (def) {
			    this.wardSelectEl.setAttribute('defaultValue','');
			    this.ward_first_time = false;
			}
		    }
	    }
	    else {
		    this.hideWardSelect();
	    }
	},
	
	updateWard: function() {		
		var sIndex = this.wardSelectEl.selectedIndex;
		this.wardTextEl.value = this.wardSelectEl.options[sIndex].value;
	},
	hideCitySelect: function(){
	    this.citySelectEl.options.length = 1;
	    if (this.cityTextEl) {
		this.cityTextEl.style.display = '';
		if(this.countryEl.value == 'VN'){
		    this.cityTextEl.disabled = true;
		    this.cityTextEl.value='';
		}
		else{
		    this.cityTextEl.disabled = false;
		    if(!this.city_first_time){
			this.cityTextEl.value='';
		    }
		}
	    }
	    this.citySelectEl.style.display = 'none';
	    Validation.reset(this.citySelectEl);
	},
	showCitySelect: function(){
	    if (this.cityTextEl) {
		this.cityTextEl.style.display = 'none';
		this.cityTextEl.disabled = false;
	    }
	    this.citySelectEl.style.display = '';
	},
	hideWardSelect: function(){
	    this.wardSelectEl.options.length = 1;
	    if (this.wardTextEl) {
		this.wardTextEl.style.display = '';
		this.wardTextEl.value='';
		if(this.countryEl.value == 'VN'){
		    this.wardTextEl.disabled = true;
		}
		else{
		    this.wardTextEl.disabled = false;
		}
	    }
	    this.wardSelectEl.style.display = 'none';
	    Validation.reset(this.wardSelectEl);
	},
	showWardSelect: function(){
	    if (this.wardTextEl) {
		this.wardTextEl.style.display = 'none';
		this.wardTextEl.disabled = false;
	    }
	    this.wardSelectEl.style.display = '';
	},
	hideWardField: function(){
	    this.wardfield.style.display = 'none';
	    this.wardTextEl.value='.';
	    this.postcodefield.style.display = '';
	},
	showWardField: function(){
	    this.wardfield.style.display = '';
	    this.wardTextEl.value='';
	    this.postcodefield.style.display = 'none';
	    this.postcodeTextbox.value = '';
	}
}