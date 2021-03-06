<?php

namespace Seat\EveQueues\Partial;

use Carbon\Carbon;
use Seat\EveApi;

class Corporation {

    public function fire($job, $data) {

        $keyID = $data['keyID'];
        $vCode = $data['vCode'];
        
        $job_record = \SeatQueueInformation::where('jobID', '=', $job->getJobId())->first();

        // Check that we have a valid jobid
        if (!$job_record) {

            // Sometimes the jobs get picked up faster than the submitter could write a
            // database entry about it. So, just wait 5 seconds before we come back and
            // try again
            $job->release(5);
            return;
        }

        // We place the actual API work in our own try catch so that we can report
        // on any critical errors that may have occurred.

        // By default Laravel will requeue a failed job based on --tries, but we
        // dont really want failed api jobs to continually poll the API Server
        try {

            $job_record->status = 'Working';
            $job_record->save();

            $job_record->output = 'Started AccountBalance Update';
            $job_record->save();
            EveApi\Corporation\AccountBalance::Update($keyID, $vCode);

            $job_record->output = 'Started ContactList Update';
            $job_record->save();
            EveApi\Corporation\ContactList::Update($keyID, $vCode);

            $job_record->output = 'Started Contracts Update';
            $job_record->save();
            EveApi\Corporation\Contracts::Update($keyID, $vCode);

            $job_record->output = 'Started CorporationSheet Update';
            $job_record->save();
            EveApi\Corporation\CorporationSheet::Update($keyID, $vCode);

            $job_record->output = 'Started IndustryJobs Update';
            $job_record->save();
            EveApi\Corporation\IndustryJobs::Update($keyID, $vCode);

            $job_record->output = 'Started MarketOrders Update';
            $job_record->save();
            EveApi\Corporation\MarketOrders::Update($keyID, $vCode);

            $job_record->output = 'Started Medals Update';
            $job_record->save();
            EveApi\Corporation\Medals::Update($keyID, $vCode);

            $job_record->output = 'Started MemberMedals Update';
            $job_record->save();
            EveApi\Corporation\MemberMedals::Update($keyID, $vCode);

            $job_record->output = 'Started MemberSecurity Update';
            $job_record->save();
            EveApi\Corporation\MemberSecurity::Update($keyID, $vCode);

            $job_record->output = 'Started MemberSecurityLog Update';
            $job_record->save();
            EveApi\Corporation\MemberSecurityLog::Update($keyID, $vCode);

            $job_record->output = 'Started MemberTracking Update';
            $job_record->save();
            EveApi\Corporation\MemberTracking::Update($keyID, $vCode);

            $job_record->output = 'Started Shareholders Update';
            $job_record->save();
            EveApi\Corporation\Shareholders::Update($keyID, $vCode);

            $job_record->output = 'Started Standings Update';
            $job_record->save();
            EveApi\Corporation\Standings::Update($keyID, $vCode);

            $job_record->output = 'Started StarbaseList Update';
            $job_record->save();
            EveApi\Corporation\StarbaseList::Update($keyID, $vCode);

            $job_record->output = 'Started StarbaseDetail Update';
            $job_record->save();
            EveApi\Corporation\StarbaseDetail::Update($keyID, $vCode);

            $job_record->status = 'Done';
            $job_record->output = null;        
            $job_record->save();

            $job->delete();

        } catch (\Exception $e) {

            $job_record->status = 'Error';
            $job_record->output = 'Last status: ' . $job_record->output . ' Error: ' . $e->getCode() . ': ' . $e->getMessage();
            $job_record->save();

            $job->delete();
        }
    }
}