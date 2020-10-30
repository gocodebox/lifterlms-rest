curl --request POST \
  --url https://example.tld/wp-json/llms/v1/students/123/progress/456 \
  --user ck_XXXXXX:cs_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"date_created":"2019-05-21T14:22:05","status":"incomplete"}'
