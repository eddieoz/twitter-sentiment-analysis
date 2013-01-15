<?php
/*****
 * Filename: 	collect_tweets.php
 * Project:	Twitter Sentiment Analysis
 * Author:	Edilson Osorio Junior
 * Data:	2013-01-09
 * 
 * This script collects tweets and writes the training file, opennlp format.
 * It has a trigger to not get blacklisted in Twitter in case of rate limit.
 *
 *
 * Selects the sentiment -> Gets the text -> strips and normalize it -> write it to a file.
 * 
 *****/

//tristeza, raiva, felicidade, alegria, nojo, medo e surpresa
$words = array('felicidade', 'nojo', 'medo', 'raiva', 'triste', 'surpresa', 'alegria');
$categories = array (
                "alegria" => array ( 'felicidade', 'alegria', 'alegre', 'feliz', 'sortudo', 'sortuda', 'sorte', 'sorridente', 'contente', 'divertido', 'diversao', 'recreio', 'fortuna', 'rico', 'milionario', 'sorte', 'riqueza', 'fartura', 'satisfeito', 'realizado', 'lindo', 'linda', 'bonito', 'bonita' ),
                "tristeza" => array ( 'tristeza' , 'triste', 'tristonho', 'infeliz', 'depre', 'depressao', 'abandonado', 'abandonada', 'sozinho', 'sozinha', 'magoado', 'magoa', 'magoada', 'aflito', 'angustia', 'pena', 'melancolia', 'melancolico' ),
                "raiva" => array ( 'raiva', 'odio', 'odeio', 'furia', 'frustrado', 'nervoso', 'nervosa', 'irritado', 'irritada', 'odiei', 'antipatico', 'chato', 'chata', 'vinganca' ),
                "amor" => array ( 'amor', 'amando', 'amado', 'querido', 'querida', 'amada', 'apaixonado', 'apaixonada', 'namorando', 'paixonite', 'afeto', 'amores', 'romance', 'romantico'),
                "medo" => array ( 'medo', 'pavor', 'apavorado', 'apavorada', 'medroso', 'medrosa', 'ciume', 'ciumento', 'ciumenta', 'fobia', 'nojo', 'terror', 'pavor' ),
                "agradecimento" => array ( 'agradecimento', 'grato', 'obrigado', 'obrigada', 'agradecido', 'agradecida', 'agradavel', 'agrado'),
                "surpresa" => array ( 'surpresa', 'surpreso', 'surpreendido', 'inesperado', 'inesperada', 'inexperado', 'inexperada', 'atonito', 'surpreendente', 'pasmo', 'perplexo', 'flagrante', 'flagra' )
		
                );


$total_files = 0;
$total_tweets = 0;
$max_files = 100;
$max_words = 1500;	
$fmax_id = fopen("max_id.txt","r");
$max_id = fgets($fmax_id);
$page = 0;
$rpp = 100;



while ( $total_files < $max_files || $total_tweets < 1000000 ){	
	$filename = date("YmdHi");
	$fp2 = fopen("data/training-$filename.txt","a");
	//foreach ($words as $word) {
	foreach (array_keys($categories) as $category) {
	  foreach ( $categories["$category"] as $word ){
	
		$find = 0;
		
		for ( $page=1; $page<=15; $page++){
			$twitter="http://search.twitter.com/search.json?rpp=" . urlencode($rpp) . "&page=" . urlencode($page) . "&max_id=" . urlencode($max_id) . "&lang=pt&q=". urlencode($word) ;
			$retry = 0;
			while ($retry <= 5 ){ // loop to do not get blacklisted on Twitter, in case of rate limits. Try 5 times and stops.
				if ( $fp = fopen( $twitter ,"r") ){ // if sucessful connection, do get tweets. If not, sleep and retry	
					while($data = fgets($fp))
					{
						$retry = 0;	
						if ( $results = json_decode( $data ) ){ // get the return page after sucessful connection
						       		foreach ($results->{'results'} as $text ) { // get tweet texts only
									$cat_parse = strtolower( $category . ' ' . trim(strip_codes( $text->{'text'} )))  .  "\n";
									fputs($fp2, $cat_parse ); // writes the opennlp format -> "'cat' 'text'"
						               		$find++;
									$total_tweets++;
									print_r ( $total_tweets . ': ' . $find . ': ' . $word . ":". $cat_parse) ;
								}				    			
						}
							
					
					}
					fclose( $fp );
					break;
				} else {
					print( "Não pode conectar no twitter ->" . $retry . "\n");
					$retry++;
					sleep(1);
					if ( $retry == 5){
						exit(1);
					}
				}
			}
		}
		if ($word == 'felicidade'){
			$max_id_felicidade=$text->{'id_str'}; // get the max_id tweet to start next search from
		}
		sleep(10);
	  }
	}
	$max_id = $max_id_felicidade;
	$fmax_id = fopen("max_id.txt","w"); 
	fputs ($fmax_id, $max_id);	// writes max_id to a file. If you need to stop, dont worry to start over.
	fclose($fmax_id);
	fclose( $fp2 );
	$total_files++;
	print ( $total_files . "\n" . $max_id );
}
fclose($fmax_id);
exit(0);

function strip_codes($text){ 

	// Strip and normalize tweet text to be cleaner 
	$text = preg_replace("/^RT +@[^ :]+:? */ui", "", $text); // Remove RT and @user
	$text = preg_replace('/#(?=[\w-]+)/', '', $text); // Remove # from hashtags
	$text = preg_replace("/@(\w+)/i", "", $text); // Remove @users
	$text = preg_replace('/\b(http|https|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', '', $text); // Remove URL
	$text = preg_replace("/(.)\\1{2,}/", "$1$1", $text); // Normalize triplicates+ to duplicate char 
	$text = str_replace( array( '“', '”', '´', '_', '\'', '"', ',' , ';', '<', '>', '.', '`'), ' ', $text); // extra chars
	$text = str_replace(array("\r\n", "\r", "\n"), '', $text); // \n \r
	$text = preg_replace("/\s\s+/", " ", $text); //extra whitespaces
	return $text;


} 

?>
