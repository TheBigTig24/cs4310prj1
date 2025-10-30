<?php

    main();

    function main() {
        generateFile();

        $startNano = hrtime(true);
        $fcfs_res = FCFS();
        echo "First-Come First-Serve Algorithm\n";
        echo $fcfs_res . "\n";
        $endNano = hrtime(true);
        $fcfs_time = _calcTime($startNano, $endNano);

        $startNano = hrtime(true);
        $sjf = SJF();
        echo "Shortest Job First Algorithm\n";
        echo $sjf;
        $endNano = hrtime(true);
        $sjf_time = _calctime($startNano, $endNano);

        $startNano = hrtime(true);
        $rr2 = roundRobin(2);
        echo "Round Robin w/ Time Slice = 2\n";
        echo $rr2;
        $endNano = hrtime(true);
        $rr2_time = _calcTime($startNano, $endNano);

        $startNano = hrtime(true);
        $rr5 = roundRobin(5);
        echo "Round Robin w/ Time Slice = 5\n";
        echo $rr5;
        $endNano = hrtime(true);
        $rr5_time = _calcTime($startNano, $endNano);

        echo "\n\n";
        echo "FCFS TIME: " . $fcfs_time . "\nSJF TIME: " . $sjf_time . "\nROUND ROBIN-2 TIME: " . $rr2_time . "\nROUND ROBIN-5 TIME: " . $rr5_time;
    }

    /**
     * Generates a job.txt file depending on user input
     */
    function generateFile() {
        // Generate job.txt file
        $jobtxt = "job.txt";
        $content = "";

        $getNumberOfJobs = readline("Enter number of jobs:\n");

        if (is_numeric($getNumberOfJobs)) {
            $numJobs = (int) $getNumberOfJobs;
            for ($i = 1; $i <= $numJobs; $i++) {
                $randNum = random_int(1, 30);
                $content .= "Job" . $i . "\n" . $randNum . "\n";
            }
        } else {
            exit("bruh that shi is not a number");
        }

        if (file_put_contents($jobtxt, $content) !== false) {
            echo "File '$jobtxt' created successfully.\n\n";
        } else {
            echo "Error creating file '$jobtxt'";
        }
    }

    /**
     * Runs First-Come First-Serve Algorithm
     * @return string final_result string representation of a gantt chart
     */
    function FCFS(): string
    {
        $file_handle = fopen('job.txt', 'r');

        $final_result = '';

        // track current gantt when inputting in final_result string
        $current_gantt_time = 0;

        if ($file_handle) {

            while (!feof($file_handle)) {
                // gets the 'Job_' line
                $job_line = fgets($file_handle);

                if (str_starts_with($job_line, 'Job')) {
                    // gets the job's burst time
                    $job_length = (int) fgets($file_handle);

                    // calculate the end bound of the gantt chart interval
                    $next_time = $current_gantt_time + $job_length;

                    // add to final_result string
                    $final_result .= "[$current_gantt_time, $next_time]$job_line\n";

                    // increment the starting bound for the next gantt chart interval
                    $current_gantt_time = $next_time;
                }

            }
        }

        return $final_result;
    }

    /**
     * Runs Shortest-Job First Algorithm
     * @return string final_result string representation of a gantt chart
     */
    function SJF(): string
    {
        // use a Custom Min Heap that compares the burst times of the job objects
        $minHeap = new CustomMinHeap();

        // keeps a concurrent array of the job names idk why i did this but i already did it
        $jobs = [];

        $final_result = '';

        $file_handle = fopen('job.txt', 'r');
        
        // reads each job and puts it in a min heap with an associated index
        if ($file_handle) {
            $index = 0;
            while (!feof($file_handle)) {
                $job_line = fgets($file_handle);
                if (trim($job_line) == "") {
                    break;
                }
                $job_length = (int) fgets($file_handle);

                $arrayObj = [
                    'index' => $index,
                    'burst_time' => $job_length,
                ];
                $index++;

                array_push($jobs, $job_line);
                $minHeap->insert($arrayObj);
            }
        }

        // run SJF alg

        $current_gantt_time = 0;

        for ($i = 0; $i < count($jobs); $i++) {
            // grab lowest time job
            $lowest_time_job = $minHeap->extract();
            
            // calculate end bound of gantt chart interval
            $next_time = $lowest_time_job['burst_time'] + $current_gantt_time;

            // find respective job name from that dumb array
            $job = $jobs[(int) $lowest_time_job['index']];

            // append to final_result
            $final_result .= "[$current_gantt_time, $next_time]$job\n";

            // increment bottom bound gantt chart interval
            $current_gantt_time = $next_time;
        }

        return $final_result;

    }

    /**
     * Custom Min Heap that compares the burst times and indices of jobs for SJF Alg
     */
    class CustomMinHeap extends SplMinHeap {
        protected function compare(mixed $thing1, mixed $thing2):int {
            $t1 = $thing1['burst_time'];
            $t2 = $thing2['burst_time'];

            if ($t1 == $t2) {
                return $thing1['index'] < $thing2['index'] ? 1 : -1; // line 114
            }

            return $t1 < $t2 ? 1 : -1;
        }
    }

    /**
     * Runs Round-Robin Algorithm
     * @param int quantum_slice int of ecah quantum time slice for the round robin algorithm
     * @return string final_result string representation of a gantt chart
     */
    function roundRobin(int $quantum_slice): string
    {
        $final_result = '';
        // use queue to represent the round robin process
        $queue = new SplQueue();
        $file_handle = fopen('job.txt', 'r');

        // read in file and hold that stuffies in a array type shizzle
        if ($file_handle) {

            while (!feof($file_handle)) {
                $job_name = fgets($file_handle);
                if (trim($job_name) == "") {
                    break;
                }
                $job_length = (int) fgets($file_handle);

                // each queue obj will hold the job name, the burst time, and the og burst time to keep track of it for the gantt chart
                $queueObj = [
                    'job_name' => $job_name,
                    'burst_time' => $job_length,
                    'original_burst_time' => $job_length,
                ];

                $queue->enqueue($queueObj);
            }
        }
       
        // run round robin

        $current_gantt_time = 0;

        while (!$queue->isEmpty()) {
            // grab item at front of queue
            $current_job = $queue->dequeue();
            /**
             * if job is about to be finished, do calculations of gantt chart interval and take it out of the queue
             * else subtract the time slice from the job and place it to the end of the queue
             */
            if ($current_job['burst_time'] <= $quantum_slice) {
                $next_time = $current_gantt_time + $current_job['original_burst_time'];
                $final_result .= "[$current_gantt_time, $next_time]{$current_job['job_name']}\n";
                $current_gantt_time = $next_time;
            } else {
                $current_job['burst_time'] -= $quantum_slice;
                $queue->enqueue($current_job);
            }

        }

        return $final_result;
    }

    /**
     * helper function to calculate time taken
     * @param int startTime in nanoseconds
     * @param int endTime in nanoseconds
     * @return int the subtracted result
     */
    function _calcTime($startTime, $endTime): int
    {
        return $endTime - $startTime;
    }
?>
