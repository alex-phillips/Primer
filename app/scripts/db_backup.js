/**
 * @author Barry Johnson
 * @source http://stackoverflow.com/questions/22109487/nodejs-mysql-dump
 *
 * @param connectionInfo
 * @param filename
 * @constructor
 */

var MysqlBackup = function(connectionInfo, filename){

    var Q = require('q');
    var self = this;
    this.backup = '';
    // my personal preference is to simply require() inline if I am only
    // going to use something a single time. I am certain some will find
    // this a terrible practice
    this.connection = require('mysql').createConnection(connectionInfo);

    function getTables(){
        //  return a promise from invoking the node-style 'query' method
        //  of self.connection with parameter 'SHOW TABLES'.
        return Q.ninvoke(self.connection,'query', 'SHOW TABLES');
    };

    function doTableEntries(theResults){

        // note that because promises only pass a single parameter around,
        // if the 'denodeify-ed' callback has more than two parameters (the
        // first being the err param), the parameters will be stuffed into
        // an array. In this case, the content of the 'fields' param of the
        // mysql callback is in theResults[1]

        var tables = theResults[0];
        // create an array of promises resulting from another Q.ninvoke()
        // query call, chained to .then(). Note that then() expects a function,
        // so recordEntry() in fact builds and returns a new one-off function
        // for actually recording the entry (see recordEntry() impl. below)

        var tableDefinitionGetters = [];
        for (var i = 0; i < tables.length ; i++){
            //  I noticed in your original code that your Tables_in_[] did not
            //  match your connection details ('mvc' vs 'test'), but the below
            //  should work and is a more generalized solution
            var tableName = tables[i]['Tables_in_'+connectionInfo.database];

            tableDefinitionGetters.push(Q.ninvoke(self.connection, 'query', 'SHOW CREATE TABLE ' + tableName)
                .then(recordEntry(tableName)) );
        }

        // now that you have an array of promises, you can use Q.allSettled
        // to return a promise which will be settled (resolved or rejected)
        // when all of the promises in the array are settled. Q.all is similar,
        // but its promise will be rejected (immediately) if any promise in the
        // array is rejected. I tend to use allSettled() in most cases.

        return Q.allSettled(tableDefinitionGetters);
    };

    function recordEntry (tableName){
        return function(createTableQryResult){
            self.backup += "DROP TABLE " + tableName + ";\n\n";
            self.backup += createTableQryResult[0][0]["Create Table"] + ";\n\n";
        };
    };

    function saveFile(){
        // Q.denodeify return a promise-enabled version of a node-style function
        // the below is probably excessively terse with its immediate invocation
        return (Q.denodeify(require('fs').writeFile))(filename, self.backup);
    }

    // with the above all done, now you can actually make the magic happen,
    // starting with the promise-return Q.ninvoke to connect to the DB
    // note that the successive .then()s will be executed iff (if and only
    // if) the preceding item resolves successfully, .catch() will get
    // executed in the event of any upstream error, and finally() will
    // get executed no matter what.

    Q.ninvoke(this.connection, 'connect')
        .then(getTables)
        .then(doTableEntries)
        .then(saveFile)
        .then( function() {console.log('Success'); } )
        .catch( function(err) {console.log('Something went awry', err); } )
        .finally( function() {self.connection.destroy(); } );
};

var myConnection = {
    host     : '127.0.0.1',
    user     : 'root',
    password : 'applepie',
    database : 'mvc'
};

// I have left this as constructor-based calling approach, but the
// constructor just does it all so I just ignore the return value

new MysqlBackup(myConnection,'../config/backups/db_backup.sql');