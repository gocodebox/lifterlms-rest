curl --request POST \
  --url https://example.tld/wp-json/llms/v1/webhooks \
  --user ck_XXXXXX:cs_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"name":"A Student Enrolled in a Course","status":"disabled","topic":"student.created","delivery_url":"https://example.tld/webhook-receipt/endpoint","secret":"$P3CI41-$3CR37!"}'