 <?php
 private function xmlToArray( $xml, $namespaces = null ) {
        //  waLog::dump($xml);
        $a = array();
        try {

            $xml->rewind();
            while ( $xml->valid() ) {
                $key = $xml->key();
                if ( ! isset( $a[ $key ] ) ) {
                    $a[ $key ] = array();
                    $i         = 0;
                } else {
                    $i = count( $a[ $key ] );
                }
                $simple = true;
                foreach ( $xml->current()->attributes() as $k => $v ) {
                    $a[ $key ][ $i ][ $k ] = (string) $v;
                    $simple                = false;
                }

                if ( $namespaces ) {
                    foreach ( $namespaces as $nid => $name ) {
                        foreach ( $xml->current()->attributes( $name ) as $k => $v ) {
                            $a[ $key ][ $i ][ $nid . ':' . $k ] = ( string ) $v;
                            $simple                             = false;
                        }
                    }
                }
                if ( $xml->hasChildren() ) {
                    if ( $simple ) {
                        $a[ $key ][ $i ] = $this->xmlToArray( $xml->current(), $namespaces );
                    } else {
                        $a[ $key ][ $i ]['content'] = $this->xmlToArray( $xml->current(), $namespaces );
                    }
                } else {
                    if ( $simple ) {
                        $a[ $key ][ $i ] = strval( $xml->current() );
                    } else {
                        $a[ $key ][ $i ]['content'] = strval( $xml->current() );
                    }
                }
                $i ++;
                $xml->next();
            }
        } catch ( Exception $ex ) {
            waLog::log( $ex->getMessage() );
            waLog::dump( 'жопа' );
        }

        return $a;
    }