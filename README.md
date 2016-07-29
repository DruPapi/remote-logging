# remote-logging

to register:
in config\app.php add:

// In providers array
Popkod\Log\Providers\LogServiceProvider::class,
And also add aliase for facade in this file like this way :

// In aliases array replace
'Log' => Popkod\Log\Facades\LogFacade::class,
