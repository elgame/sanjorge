var util = {

  // Trunca a 2 decimales
  trunc2Dec: function (num) {
    return Number(num.toString().match(/^\d+(?:\.\d{0,2})?/));
    // return Math.floor(num * 100) / 100;
  },

  // Redondea a 2 decimales
  round2Dec: function (val) {
    return Math.round(val * 100) / 100;
  },

	//dar dormato a un numero estilo moneda
	darFormatoNum: function(moneda, prefix, conSigno){
		var precio = '' + parseFloat(moneda),
		strPrecio = '',
		cont = 0,
		posini = 0,
		i = 0,
		aux = '';
		prefix = prefix==undefined? '$': prefix;
		conSigno = (conSigno==false)?false:true;

		if(precio.indexOf('.')!=-1){
			posini = precio.indexOf('.')-1;
			entero = false;
		}else{
			posini = (precio.length)-1;
			entero = true;
		}

		for(i=posini; i>=0; i-=1){
			cont +=1;
			aux = strPrecio;
			strPrecio = precio[i] + aux;
			if(cont==3 && i!=0){
				cont=0;
				aux = strPrecio;
				strPrecio = ',' + aux;
			}
		}

		if(entero==true)
			strPrecio += '.00';
		else{
			strPrecio += precio.substr(posini+1,3);
			if((precio.substr(posini+2,precio.length-posini+2).length)==1)
				strPrecio += '0';
		}
		return (conSigno?prefix+strPrecio: util.quitarFormatoNum(strPrecio));
	},
	//quitar el formato a un numero
	quitarFormatoNum: function(num){
		return num.replace(/([^0-9\.\-])/g, "")*1;
	},
	//OBJETO QUE PASA UN NUMERO A LETRA
	numeroToLetra: {
		//Función modulo, regresa el residuo de una división
		mod: function(dividendo , divisor){
			/*resDiv = dividendo / divisor ;
			parteEnt = Math.floor(resDiv); // Obtiene la parte Entera de resDiv
			parteFrac = resDiv - parteEnt ; // Obtiene la parte Fraccionaria de la división
			modulo = Math.round(parteFrac * divisor);  // Regresa la parte fraccionaria * la división (modulo)*/
			modulo = parseInt(dividendo % divisor);
			return modulo;
		},
		// Función ObtenerParteEntDiv, regresa la parte entera de una división
		ObtenerParteEntDiv: function(dividendo , divisor){
			resDiv = dividendo / divisor ;
			parteEntDiv = Math.floor(resDiv);
			return parteEntDiv;
		},
		//regresa la parte Fraccionaria de una cantidad
		fraction_part: function(dividendo , divisor){
			resDiv = dividendo / divisor ;
			f_part = Math.floor(resDiv);
			return f_part;
		},
		string_literal_conversion: function(number){
			centenas = this.ObtenerParteEntDiv(number, 100);

			number = this.mod(number, 100);

			decenas = this.ObtenerParteEntDiv(number, 10);
			number = this.mod(number, 10);

			unidades = this.ObtenerParteEntDiv(number, 1);
			number = this.mod(number, 1);
			string_hundreds="";
			string_tens="";
			string_units="";

			//Combierte la parte CIENTOS
			switch(centenas){
				case 1: string_hundreds = "ciento "; break;
				case 2: string_hundreds = "doscientos "; break;
				case 3: string_hundreds = "trescientos "; break;
				case 4: string_hundreds = "cuatrocientos "; break;
				case 5: string_hundreds = "quinientos "; break;
				case 6: string_hundreds = "seiscientos "; break;
				case 7: string_hundreds = "setecientos "; break;
				case 8: string_hundreds = "ochocientos "; break;
				case 9: string_hundreds = "novecientos "; break;
			}

			//combierte la parte DECENAS
			switch(decenas){
				case 1:
					switch(unidades){
						case 1: string_tens = "once"; break;
						case 2: string_tens = "doce"; break;
						case 3: string_tens = "trece"; break;
						case 4: string_tens = "catorce"; break;
						case 5: string_tens = "quince"; break;
						case 6: string_tens = "dieciseis"; break;
						case 7: string_tens = "diecisiete"; break;
						case 8: string_tens = "dieciocho"; break;
						case 9: string_tens = "diecinueve"; break;
					}
				break;
				case 2: string_tens = "veinti"; break;
				case 3: string_tens = "treinta"; break;
				case 4: string_tens = "cuarenta"; break;
				case 5: string_tens = "cincuenta"; break;
				case 6: string_tens = "sesenta"; break;
				case 7: string_tens = "setenta"; break;
				case 8: string_tens = "ochenta"; break;
				case 9: string_tens = "noventa"; break;
			}

			//combierte la parte de UNIDADES
			if (decenas == 1){
				string_units="";
			}else{
				switch(unidades){
					case 1: string_units = "un"; break;
					case 2: string_units = "dos"; break;
					case 3: string_units = "tres"; break;
					case 4: string_units = "cuatro"; break;
					case 5: string_units = "cinco"; break;
					case 6: string_units = "seis"; break;
					case 7: string_units = "siete"; break;
					case 8: string_units = "ocho"; break;
					case 9: string_units = "nueve"; break;
				}
			}

			//Ajustes de algunas fraces
			if (centenas == 1 && decenas == 0 && unidades == 0){
				string_hundreds = "cien " ;
			}
			if (decenas == 1 && unidades ==0){
				string_tens = "diez " ;
			}
			if (decenas == 2 && unidades ==0){
				string_tens = "veinte " ;
			}
			if (decenas >=3 && unidades >=1){
				string_tens = string_tens+" y ";
			}

			final_string = string_hundreds+string_tens+string_units;
			return final_string ;
		},
		covertirNumLetras: function (number, tipoMoneda){
			number1=number;
			cent = number1.split('.');
			centavos = cent[1];
			millions_final_string ="";
			thousands_final_string ="";

			if (centavos == 0 || centavos == undefined){
				centavos = "00";
			}
			if (number == 0 || number == ""){
				centenas_final_string=" cero ";
			}else{
				millions  = this.ObtenerParteEntDiv(number, 1000000);
				number = this.mod(number, 1000000);

				if (millions != 0){
					if (millions == 1){
						descriptor= " millon ";
					}else{
						descriptor = " millones ";
					}
				}else{
					descriptor = " ";
				}
				millions_final_string = this.string_literal_conversion(millions)+descriptor;


				thousands = this.ObtenerParteEntDiv(number, 1000);
				number = this.mod(number, 1000);
				//print "Th:".thousands;
				if (thousands != 1){
					thousands_final_string = this.string_literal_conversion(thousands) + " mil ";
				}
				if(thousands == 1){
					thousands_final_string = " mil ";
				}
				if (thousands < 1){
					thousands_final_string = " ";
				}

				centenas = number;
				centenas_final_string = this.string_literal_conversion(centenas);

			}
			/* Concatena los millones, miles y cientos*/
			cad = millions_final_string+thousands_final_string+centenas_final_string;

			/* Convierte la cadena a Mayúsculas*/
			cad = cad.toUpperCase();

			if (centavos.length>2){
				if(centavos.substring(2,3)>= 5){
					centavos = centavos.substring(0,1)+(parseInt(centavos.substring(1,2))+1).toString();
				}else{
					centavos = centavos.substring(0,2);
				}
			}

			/* Concatena a los centavos la cadena "/100" */
			if(centavos.length==1){
				centavos += "0";
			}
			centavos += "/100";


			/* Asigna el tipo de moneda, para 1 = PESO, para distinto de 1 = PESOS*/
			tipoMoneda = tipoMoneda?tipoMoneda:'MXN';
      tipoMonedaCodigo = this.obtenerTipoMoneda(tipoMoneda);
      moneda = number == 1 ? tipoMonedaCodigo[0] : tipoMonedaCodigo[1];

			/* Regresa el número en cadena entre paréntesis y con tipo de moneda y la fase M.N.*/
			return $.trim(cad)+" "+$.trim(moneda)+" "+$.trim(centavos)+" "+tipoMoneda;
		},
    obtenerTipoMoneda: function (codigo) {
      // Almacena todas los codigos de monedas que son de dolar.
      var codigosTiposDolares = {
        'AUD': 1, 'BBD': 1, 'BMD': 1, 'BND': 1, 'BSD': 1, 'BZD': 1, 'CAD': 1, 'FJD': 1, 'GYD': 1, 'HKD': 1, 'JMD': 1, 'KYD': 1, 'LRD': 1, 'NAD': 1, 'NZD': 1, 'SBD': 1, 'SGD': 1, 'SRD': 1, 'TTD': 1, 'TWD': 1, 'USD': 1, 'ZWL': 1
      },

      codigoTipoFrancos = { 'BIF': 1, 'CDF': 1, 'CHF': 1, 'DJF': 1, 'GNF': 1, 'RWF': 1 },

      codigoTipoPesos = { 'ARS': 1, 'CLP': 1, 'COP': 1, 'CUP': 1, 'DOP': 1, 'MXN': 1, 'M.N.': 1, 'PHP': 1 },

      codigoTipoLibras = { 'EGP': 1, 'FKP': 1, 'GBP': 1, 'LBP': 1, 'SHP': 1, 'SYP': 1 },

      codigoTipoChelines = { 'KES': 1, 'SOS': 1, 'TZS': 1, 'UGX': 1 },

      codigoTipoRupias = { 'IDR': 1, 'INR': 1, 'LKR': 1, 'MUR': 1, 'NPR': 1, 'PKR': 1, 'SCR': 1 },

      codigoTipoEuro = { 'EUR': 1 };

      // Dolares
      if (codigosTiposDolares.hasOwnProperty(codigo)) {
        return ['DOLAR', 'DOLARES'];
      }

      // Francos
      if (codigoTipoFrancos.hasOwnProperty(codigo)) {
        return ['FRANCO', 'FRANCOS'];
      }

      // Pesos
      if (codigoTipoPesos.hasOwnProperty(codigo)) {
        return ['PESO', 'PESOS'];
      }

      // Libras
      if (codigoTipoLibras.hasOwnProperty(codigo)) {
        return ['LIBRA', 'LIBRAS'];
      }

      // Chelines
      if (codigoTipoChelines.hasOwnProperty(codigo)) {
        return ['CHELIN', 'CHELINES'];
      }

      // Rupias
      if (codigoTipoRupias.hasOwnProperty(codigo)) {
        return ['RUPIA', 'RUPIAS'];
      }

      // Euro
      if (codigoTipoEuro.hasOwnProperty(codigo)) {
        return ['EURO', 'EUROS'];
      }
    }
	},

	// Obtiene las hrs transcurridas entre 2 horas
	// Formato hh:mm
	restarHoras: function(inicio, fin) {
		// 0:hrs,1:min
		inicio = inicio.split(':');
		if (inicio.length != 2) return false;
		// 0:hrs,1:min
		fin = fin.split(':');
		if (fin.length != 2) return false;

		transcurridoMinutos = fin[1] - inicio[1];
		transcurridoHoras = fin[0] - inicio[0];

		if (transcurridoMinutos < 0) {
			transcurridoHoras--;
			transcurridoMinutos = 60 + transcurridoMinutos;
		}

		horas = transcurridoHoras.toString();
		minutos = transcurridoMinutos.toString();

		if (horas.length < 2) {
			horas = "0"+horas;
		}
		if (horas.length < 2) {
			horas = "0"+horas;
		}
		if (minutos.length < 2) {
			minutos = "0"+minutos;
		}
		return horas+":"+minutos;
	}
};