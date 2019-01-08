import edu.uci.ics.crawler4j.crawler.WebCrawler;

import java.io.*;
import java.util.Arrays;
import java.util.HashSet;
import java.util.Set;
import edu.uci.ics.crawler4j.crawler.Page;
import edu.uci.ics.crawler4j.parser.HtmlParseData;
import edu.uci.ics.crawler4j.url.WebURL;


public class MyCrawler extends WebCrawler {
    private final static Set<String>FILTER = new HashSet<>(Arrays.asList("text/html", "image/png", "image/jpeg",
            "image/gif", "application/pdf", "application/json", "application/rss+xml"));
    @Override
    public boolean shouldVisit(Page referringPage, WebURL url) {
        String href = url.getURL().toLowerCase();
        boolean reside = href.startsWith("https://nypost.com/") || href.startsWith("http://nypost.com/");
        FileWriter pw = null;
        try {
            pw = new FileWriter("urls_nypost.csv",true);

            StringBuilder sb = new StringBuilder();

            sb.append(url.getURL());
            sb.append(',');
            if(reside)
                sb.append("OK");
            else
                sb.append("N_OK");
            sb.append('\n');

            pw.write(sb.toString());
            pw.close();
        } catch (IOException e) {
            e.printStackTrace();
        }
        return FILTER.contains(referringPage.getContentType().split(";")[0]) && reside;
    }

    @Override
    public void handlePageStatusCode(WebURL webUrl, int statusCode, String statusDescription) {
        FileWriter pw = null;
        try {
            pw = new FileWriter("fetch_nypost.csv",true);

            StringBuilder sb = new StringBuilder();

            sb.append(webUrl.getURL());
            sb.append(',');
            sb.append(statusCode);
            sb.append('\n');

            pw.write(sb.toString());
            pw.close();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    @Override
    public void visit(Page page) {

        String url = page.getWebURL().getURL();
        System.out.println("URL: " + url);
        if (page.getParseData() instanceof HtmlParseData) {
            HtmlParseData htmlParseData = (HtmlParseData) page.getParseData();
            String text = htmlParseData.getText();
            String html = htmlParseData.getHtml();
            Set<WebURL> links = htmlParseData.getOutgoingUrls();

            System.out.println("Text length: " + text.length());
            System.out.println("Html length: " + html.length());
            System.out.println("Number of outgoing links: " + links.size());

            FileWriter pw = null;
            try {
                pw = new FileWriter("visit_nypost.csv",true);

                StringBuilder sb = new StringBuilder();

                sb.append(url);
                sb.append(',');
                sb.append(page.getContentData().length);
                sb.append(',');
                sb.append(links.size());
                sb.append(',');
                sb.append(page.getContentType().split(";")[0]);
                sb.append('\n');

                pw.write(sb.toString());
                pw.close();
            } catch (IOException e) {
                e.printStackTrace();
            }
        }
    }
}
