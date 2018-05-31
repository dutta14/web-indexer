import networkx as nx

G = nx.read_edgelist("edgeList.txt", create_using=nx.DiGraph())

pr = nx.pagerank(G, max_iter=30)

f = open('external_pageRankFile.txt', 'w')
path = "C:\\Users\\anind\\Downloads\\NBC_News-20180407T052036Z-001\\NBC_News\\HTML files\\HTML files\\"

for k,v in pr.items():
    f.write(''.join([path, str(k), "=", str(v), "\n"]))
f.close()
