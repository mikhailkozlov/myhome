@servers(['host' => 'mkozlov@10.0.2.2'])

@task('service', ['on' => 'host'])
cd /Volumes/StorageHD/Users/mkozlov/localhost/myhome
/usr/local/bin/node /Users/mkozlov/localhost/myhome/service.js
@endtask