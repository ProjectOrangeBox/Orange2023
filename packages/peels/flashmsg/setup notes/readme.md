Include the file notify.js on each page that needs to show flash messages

(or just add it to every page)

ie. add a few flash messages redirect to another page and then they pop up

also make sure to edit notify.js line 6 and make sure it matches your view variable which is populated by your server when building the page.

```<script>var flashMsgs = "[{text:'Foo',style:'success'},{text:'Bar',style:'danager'}]";</script>```

the variable is "flashMsgs" in this case so line 6 should be: 

```notify.messages = flashMsgs;```