curl --request PATCH \
  --url 'https://example.tld/wp-json/llms/v1/students/123/enrollments/456?trigger=SOME_STRING_VALUE' \
  --user ck_XXXXXX:cs_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"date_created":"2019-05-21 14:22:05","status":"enrolled"}'