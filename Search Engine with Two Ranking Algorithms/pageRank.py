import networkx as nx

G = nx.read_edgelist("/Users/xuechengzhe/IdeaProjects/csci572HW4/edgeList.txt", create_using=nx.DiGraph())
pr = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight', dangling=None)
f = open("external_pageRankFile.txt", "a")
for x in pr:
    f.write("/Users/xuechengzhe/solr-7.5.0/../nypost/%s=%s\n" % (x, pr[x]))
f.close()
