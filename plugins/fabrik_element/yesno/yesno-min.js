/*! Fabrik */
FbYesno=my.Class(FbRadio,{constructor:function(a,b){this.plugin="fabrikyesno",this.parent(a,b)},getChangeEvent:function(){return this.options.changeEvent}});