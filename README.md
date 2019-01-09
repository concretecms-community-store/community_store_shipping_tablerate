# community_store_tablerate

Please note there are two branches of this add-on.
The master branch is currently at version 2.0, and is intended for use with version 2.x of Community Store (the v8-only version). Please do not perform an upgrade of this add-on from version 1 to version 2.

A 1.x-compatible branch is available for version 1.x of Community Store.


In this package you can choose 3 different conditions as a base for your table rates.  
1. Number of items vs destination  
2. Price vs destination  
3. Weight vs destination  

The table rates are inserted by uploading a csv (comma separated values) file. This csv-file are the same as the ones “Magento” uses.   
When searching on the internet for “Magento table rate generator”, you should find some generators to create a csv file compatible with this package.  
Important: Concrete5 uses 2-letter codes, so be sure the generator outputs the countries in 2-letter codes. Example : https://www.elgentos.nl/tablerates/?twoLetterCodes   

Notice : This package only checks country and not state/region.  
Requirements : PHP 5.3 (to read the csv file)  


