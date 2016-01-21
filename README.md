# moodle-block_course_files_license
A Moodle block that allow teachers to identified licenses/copyright of the files their are using on his course

Optional parameters in config.php
-------------------

#### Filter by extensions
It is possible to limit the query by extension. By default no filter by extension is made.

```json
$CFG->licensefilesextensions = array('odt', 'doc', 'docx', 'pdf');
```

#### Anoying modal window
If set to true, this option will show an annoying modal windows whenever user has files to identify. Default: false.

```json
$CFG->licensefilesmodal = true;
```
#### Custom information message
In the identification file list view of each course you can use a custom message to show some custom information, notes or instructions (i.e. your organization name, law, or whatever).

```json
$CFG->licensefilestextinfo = 'Here you can write a large text including HTML'
```
