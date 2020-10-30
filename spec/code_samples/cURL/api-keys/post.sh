curl --request POST \
  --url https://example.tld/wp-json/llms/v1/api-keys \
  --user ck_XXXXXX:cs_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"user_id":456,"description":"My API Key","permissions":"read"}'
