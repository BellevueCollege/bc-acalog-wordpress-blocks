<?php
namespace bawb\acalog_api;

/**
* Class for interacting with Acalog API (SOAP)
*
* @package Acalog
* @since 1.0.0
*/
class API {
    private $api_base_url = '';
    private $api_key = null;
    private $transient_base = 'acalog_api';
    private $transient_ttl = 1 * HOUR_IN_SECONDS;

    /**
     * Acalog_API constructor.
     *
     * @param string $api_base_url
     * @param string|null $api_key
     */
    public function __construct(
        string $api_base_url,
        ?string $api_key = null,
        ) {
        $this->api_base_url = $api_base_url;
        $this->api_key      = $api_key ?? ACALOG_API_KEY ?? null;
    }

    /**
     * Get Acalog URL with API key
     * 
     * @param string $endpoint
     * @return string
     */
    private function get_base_url( $endpoint = '' ): string {
        return $this->api_base_url . $endpoint . '?key=' . $this->api_key;
    }

    /**
     * Get the Active Catalog ID
     *
     * @return int|null
     */
    public function get_active_catalog_id(): ?int {

        // Set name for transient (used to cache results)
        $transient_name = $this->transient_base . '_active_catalog';

        // Check transient to see if results are cached
        if ( false === ( $catalog_id = get_site_transient( $transient_name ) ) ) {

            // Build request URL
            $url = $this->get_base_url( '/content') . '&format=xml&method=getCatalogs';

            // Load XML
            $xml = simplexml_load_file( "$url" ) or throw( new Exception( "Error: Cannot load Catalog XML" ) );

            // Get Active Catalog
            foreach ( $xml[0]->catalog  as $catalog ) {
                if ( $catalog->state->published == 'Yes' && $catalog->state->archived == 'No' ) {
                    $catalog_id = (int)str_replace( 'acalog-catalog-', '', $catalog->attributes()->id );
                    break;
                }
            }
        
            // Put the results in a transient.
            set_site_transient( $transient_name, $catalog_id, $this->transient_ttl + rand( 0, 60 ) );
        }
        return $catalog_id;
    }


    /**
     * Get All Programs from Catalog
     * 
     * @param int|null $catalog_id
     * @return array
     * @throws Exception
     */
    public function get_all_programs( ?int $catalog_id = null ): array {

        // Get Active Catalog if not provided
        $catalog_id = $catalog_id ?? $this->get_active_catalog_id();

        // Set name for transient (used to cache results)
        $transient_name = $this->transient_base . '_all_programs_cat_' . $catalog_id;

        // Check transient to see if results are cached
        if ( false === ( $programs = get_site_transient( $transient_name ) ) ) {

            // Build request URL
            $url = $this->get_base_url( '/search/programs') . "&format=xml&method=listing&catalog=$catalog_id&options[sort]=alpha&options[limit]=0";

            // Load XML
            $xml = simplexml_load_file( "$url" ) or throw ( new Exception( "Error: Cannot load Program XML" ) );

            // Get Active Programs
            $programs = [];
            foreach ( $xml[0]->search->results->result as $program ) {
                if ( $program->state->name == 'Active-Visible' ) {
                    $programs[sanitize_title((string)$program->name)] = array(
                        'id' => (int)$program->id,
                        'name' => (string)$program->name,
                    );
                }
            }
            // Put the results in a transient.
            set_site_transient( $transient_name, $programs, $this->transient_ttl + rand( 0, 60 ) );
        }
        return $programs;
    }

    /**
     * Get program id by name. Causes an API call for each program. AVOID USING.
     *
     * @param string $program_name
     * @param int|null $catalog_id
     * @throws Exception Error: Program not found
     * @return int
     */
    public function get_program_id_by_name_direct ( $program_name, $catalog_id = null ) : int|Exception {

        $transient_name = $this->transient_base . '_' . sanitize_key( $program_name ) . '_cat_' . $catalog_id;

        if ( false === ( $program_id = get_site_transient( $transient_name ) ) ) {
            $catalog_id = $catalog_id ?? $this->get_active_catalog_id();
            $program_name_encoded = esc_attr( "$program_name" );
            $url = $this->get_base_url( '/search/programs') . "&format=xml&method=search&catalog=$catalog_id&query=$program_name_encoded&options[sort]=rank&options[limit]=10";
            $xml = simplexml_load_file( "$url" ) or throw ( new Exception( "Error: Cannot load Program XML" ) );

            foreach ( $xml[0]->search->results->result  as $program ) {
                if ( $program->name == $program_name ) {
                    // Put the results in a transient.
                    $program_id = (int)$program->id;
                    set_site_transient( $transient_name, $program_id, $this->transient_ttl + rand( 0, 60 ) );
                    break;
                }
            }
        }

        // Return results if set
        if ( $program_id ) {
            return $program_id ?? 0;
        }

        // Error if no program found
        throw new Exception( "Error: Program not found" );
    }

    /**
     * Get Program ID by Program Name, Using Full Cached List of Programs
     * 
     * @param string $program_name
     * @param int|null $catalog_id
     * @return int
     * @throws Exception
     */
    public function get_program_id_by_name ( $program_name, $catalog_id = null ) : int|Exception {

        // Get Active Catalog if not provided
        $catalog_id = $catalog_id ?? $this->get_active_catalog_id();

        // Get All Programs
        $programs = $this->get_all_programs( $catalog_id );

        // Check if program exists, return id if it does
        if ( isset( $programs[sanitize_title($program_name)] ) ) {
            return $programs[sanitize_title($program_name)]['id'];
        }

        throw new Exception( "Error: Program not found" );
    }

    /**
     * Generate Program Link
     * 
     * @param int $program_id
     * @param int $catalog_id
     * @return string
     */
    public function generate_program_link ( $program_id, $catalog_id ) : string {
        return ACALOG_BASE_URL . "/preview_program.php?catoid=$catalog_id&poid=$program_id";
    }
    
    /**
     * Get Program Link by Program Name
     * 
     * @param string $program_name
     * @param int|null $catalog_id
     * @return string
     */
    public function get_program_link_by_name ( $program_name, $catalog_id = null ) : string {
        try {
            $catalog_id = $catalog_id ?? $this->get_active_catalog_id();
            $program_id = $this->get_program_id_by_name( $program_name, $catalog_id );
        } catch ( \Exception $e ) {
            //print_r( $e );
            error_log( 'Acalog Program Link Exception: ' . $e->getMessage() );
            return ACALOG_BASE_URL . '#exception';
        } catch ( \Throwable $e ) {
            //print_r( $e );
            error_log( 'Acalog Program Link Exception (Unknown): ' . $e->getMessage() );
            return ACALOG_BASE_URL . '#unknown-exception';
        }
        
        return $this->generate_program_link( $program_id, $catalog_id );
    }
}


