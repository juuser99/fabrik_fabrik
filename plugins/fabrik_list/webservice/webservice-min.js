/*! Fabrik */
var FbListWebservice=my.Class(FbListPlugin,{constructor:function(a){FbListWebservice.Super.call(this,a)},buttonAction:function(){this.list.submit("list.doPlugin")}});