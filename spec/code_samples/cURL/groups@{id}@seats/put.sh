curl --request PUT \
  --url https://example.tld/wp-json/llms/v1/groups/123/seats \
  --user ck_XXXXXX:cs_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"total":20}'