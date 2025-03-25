# OpenEuropa Search Demo

---

This demo module is used to showcase the configuration
needed for Europa Search to retrieve and index content.

It brings a configured SearchAPI Server, SearchAPI Index and a view that shows basic information.

After enabling the module, configuration should be altered to contain the correct endpoints and credentials.

It is recommended to do it altering the configuration directly in Settings.php

```
if (getenv('EUROPA_SEARCH_ENABLED')) {
  // Europa Search settings.
  $config['search_api.server.europa_search_server']['status'] = (bool) trim(getenv('EUROPA_SEARCH_ENABLED'));
  $config['search_api.server.europa_search_server']['backend_config']['api_key'] = trim(getenv('EUROPA_SEARCH_API_KEY'));
  $config['search_api.server.europa_search_server']['backend_config']['database'] = trim(getenv('EUROPA_SEARCH_DATABASE'));
  $config['search_api.server.europa_search_server']['backend_config']['search']['endpoint']['info'] = trim(getenv('EUROPA_SEARCH_ENDPOINT_INFO'));
  $config['search_api.server.europa_search_server']['backend_config']['search']['endpoint']['search'] = trim(getenv('EUROPA_SEARCH_ENDPOINT_SEARCH'));
  $config['search_api.server.europa_search_server']['backend_config']['search']['endpoint']['facet'] = trim(getenv('EUROPA_SEARCH_ENDPOINT_FACET'));

  // Europa Search ingestion settings.
  $settings['oe_search']['server']['europa_search_server']['consumer_key'] = trim(getenv('EUROPA_SEARCH_CONSUMER_KEY'));
  $settings['oe_search']['server']['europa_search_server']['consumer_secret'] = trim(getenv('EUROPA_SEARCH_CONSUMER_SECRET'));
  $settings['oe_search']['server']['europa_search_server']['site_id'] = trim(getenv('EUROPA_SEARCH_SITE_ID'));
  $config['search_api.server.europa_search_server']['backend_config']['ingestion']['endpoint']['token'] = trim(getenv('EUROPA_SEARCH_INGESTION_ENDPOINT_TOKEN'));
  $config['search_api.server.europa_search_server']['backend_config']['ingestion']['endpoint']['text'] = trim(getenv('EUROPA_SEARCH_INGESTION_ENDPOINT_TEXT'));
  $config['search_api.server.europa_search_server']['backend_config']['ingestion']['endpoint']['file'] = trim(getenv('EUROPA_SEARCH_INGESTION_ENDPOINT_FILE'));
  $config['search_api.server.europa_search_server']['backend_config']['ingestion']['endpoint']['delete'] = trim(getenv('EUROPA_SEARCH_INGESTION_ENDPOINT_DELETE'));
}
```

The values can now be controlled using environment variables. You can get the the correct values for each of the following values,
during the onboard process with corporate search.

```
      EUROPA_SEARCH_ENABLED: 1
      EUROPA_SEARCH_DATABASE: "YOUR_API_KEY"
      EUROPA_SEARCH_API_KEY: "YOUR_API_KEY"
      EUROPA_SEARCH_SITE_ID: "YOUR_SITE_ID_TO_INDEX"
      EUROPA_SEARCH_ENDPOINT_INFO: ""
      EUROPA_SEARCH_ENDPOINT_SEARCH: ""
      EUROPA_SEARCH_ENDPOINT_FACET: ""
      EUROPA_SEARCH_INGESTION_ENDPOINT_TOKEN: ""
      EUROPA_SEARCH_INGESTION_ENDPOINT_TEXT: ""
      EUROPA_SEARCH_INGESTION_ENDPOINT_FILE: ""
      EUROPA_SEARCH_INGESTION_ENDPOINT_DELETE: ""
      EUROPA_SEARCH_CONSUMER_SECRET: "YOUR_CONSUMER_SECRET"
      EUROPA_SEARCH_CONSUMER_KEY: "YOUR_CONSUMER_KEY"
```
