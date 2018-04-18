
function calcTime( mydate, offset )
{
    // create Date object for current location
    d = new Date( mydate );
	//alert( d.toLocaleString() );
    // convert to msec
    // add local time zone offset
    // get UTC time in msec
	var diff = ( 3600 * offset ) - ( d.getTimezoneOffset() * 60 );
    
    //alert( diff / 60 / 60 );
    
    return mydate + ( diff * 1000 );
}

function isNotEmpty(elem)
{
	var str = elem.value;
	if(str == null || str.length ==0)
	{
		return false;
	}
	return true;
}

function isNumber(elem)
{
	var str = elem.value;
	var oneDecimal = false;
	var oneChar = 0;
	//make sure the value hasn't cast to a number data type
	str = str.toString();
	var strLength = str.length;
	for(var i=0;i<strLength;i++)
	{
		oneChar = str.charAt(i).charCodeAt(0);
		//okay for minus slign as first character
		
		if(oneChar == 45)
		{
			if(i!=0)
			{
				return false;
			}
		}
		
		//okay for one decimal point
		
		if(oneChar == 46)
		{
			if(!oneDecimal)
			{
				oneDecimal = true;
			}
			else
			{
				return false;
			}
		}
	
		if(oneChar < 48 || oneChar > 57)
		{
			return false;
		}
	}
	return true;
}

function isEmailAddr(elem)
{
	var str = element.value;
	var re = /^[\w-]+(\.[\w-]+)*@([\w-]+\.)+[a-zA-Z]{2,7}$/
	
	return str.match(re);
}


