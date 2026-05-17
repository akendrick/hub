# Calendar diagnostic — paste ONLY between the fences into TRMNL markup

```html
<p>generated: ##{{ IDX_0.generated }}</p>
<p>Loop test:</p>
##{% for d in IDX_0.days limit:3 %}
<p>##{{ d.dow }} ##{{ d.dom }} / ##{{ d.holiday }}</p>
##{% endfor %}
```
