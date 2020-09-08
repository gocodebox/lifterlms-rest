curl --request DELETE \
  --url https://example.tld/wp-json/llms/v1/students/123 \
  --user ck_XXXXXX:sk_XXXXXX \
  --header 'content-type: application/json' \
  --data '{"reassign":456}'