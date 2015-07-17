/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.tool.paralleljobs");
pimcore.tool.paralleljobs = Class.create({

    initialize: function (config) {

        this.config = config;

        this.groupsFinished = 0;
        this.groupsTotal = this.config.jobs.length;
        this.alloverJobs = 0;
        this.alloverJobsFinished = 0;

        for(var i=0; i<this.groupsTotal; i++) {
            this.alloverJobs += this.config.jobs[i].length;
        }
        
        this.groupStart();
    },

    groupStart: function () {

        this.jobsRunning = 0;
        this.jobsFinished = 0;
        this.jobsStarted = 0;
        this.jobsTotal = this.config.jobs[this.groupsFinished].length;

        this.jobsInterval = window.setInterval(this.processJob.bind(this),50);
    },

    groupFinished: function () {

        this.groupsFinished++;

        if(this.groupsFinished < this.groupsTotal) {
            this.groupStart();
        } else {
            // call success callback
            if(typeof this.config.success == "function") {
                this.config.success();
            }
        }
    },

    error: function (message) {
        if(typeof this.config.failure == "function") {
            this.config.failure(message);
        }
    },

    processJob: function () {

        var maxConcurrentJobs = 10;

        if(this.jobsFinished == this.jobsTotal) {
            clearInterval(this.jobsInterval);

            this.groupFinished();
            return;
        }

        if(this.jobsRunning < maxConcurrentJobs && this.jobsStarted < this.jobsTotal) {

            this.jobsRunning++;

            Ext.Ajax.request({
                url: this.config.jobs[this.groupsFinished][this.jobsStarted].url,
                success: function (response) {

                    try {
                        var res = Ext.decode(response.responseText);
                        if(!res["success"]) {
                            // if the download fails, stop all activity
                            throw res;
                        }
                    } catch (e) {
                        clearInterval(this.jobsInterval);
                        console.log(e);
                        console.log(response);
                        this.error( (res && res["message"]) ? res["message"] : response.responseText);
                        return;
                    }

                    this.jobsFinished++;
                    this.jobsRunning-=1;
                    this.alloverJobsFinished++;

                    // update
                    var status = this.alloverJobsFinished / this.alloverJobs;
                    var percent = Math.ceil(status * 100);

                    try {
                        if(typeof this.config.update == "function") {
                            this.config.update(this.alloverJobsFinished, this.alloverJobs, percent);
                        }
                    } catch (e2) {}

                }.bind(this),
                failure: function (response) {
                    clearInterval(this.jobsInterval);
                    this.error(response.responseText);
                }.bind(this),
                params: this.config.jobs[this.groupsFinished][this.jobsStarted].params
            });

            this.jobsStarted++;
        }
    }
});
