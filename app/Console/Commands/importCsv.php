<?php

namespace App\Console\Commands;

use GuzzleHttp\RequestOptions;
use Illuminate\Console\Command;
use League\Csv\Reader;
use League\Csv\Statement;

class importCsv extends Command
{
    private $client;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import csv';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $stream = fopen(public_path('doc/base.CSV'), 'r');
            $csv = Reader::createFromStream($stream);
            $csv->setDelimiter(';');
            $csv->setHeaderOffset(0);

            $stmt = (new Statement());

            $records = $stmt->process($csv);

            $this->output->progressStart(count($records));
            foreach ($records as $record) {
                $response = $this->client->post( 'http://127.0.0.1:3333/create',
                [
                    RequestOptions::JSON => [
                        'cnpj'  => $record['CNPJ'],
                        'cep'   => $record['CEP'],
                        'fixo'  => $record['FIXO']
                    ]
                ]);

                if($response->getStatusCode() != 200) {
                    throw new \Exception('Ocorreu um erro no cadastro do cnpj '.$record['CNPJ']);
                }

                $this->output->progressAdvance();
            }
            $this->output->progressFinish();
        } catch (\Exception $e) {
            dump($e->getMessage());
        }
    }
}
