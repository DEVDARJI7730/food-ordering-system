-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: food_order_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `catagories`
--

DROP TABLE IF EXISTS `catagories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catagories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catagories`
--

LOCK TABLES `catagories` WRITE;
/*!40000 ALTER TABLE `catagories` DISABLE KEYS */;
INSERT INTO `catagories` VALUES (1,'Starters & Appetizers','Crispy and savory appetizers to start your meal.','2026-10-05 12:09:33'),(2,'Main Course','Hearty and rich main dishes.','2026-10-05 12:09:33'),(3,'Fast Food & Pizzas','Burgers, pizzas, fries and street food delights.','2026-10-05 12:09:33'),(4,'Desserts','Sweet indulgences and treats.','2026-10-05 12:09:33'),(5,'Beverages & Coffee','Refreshing drinks, shakes and specialty coffee.','2026-10-05 12:09:33'),(6,'Side Kicks & Garlic Breads','Oven-baked breads, fries and side dishes.','2026-10-05 12:09:33'),(7,'Fresh Salads & Salad Bar','Fresh salad bowls and cold pasta salads.','2026-10-05 12:09:33'),(8,'Premium Pizzas','Handcrafted premium Italian pizzas.','2026-10-05 12:09:33'),(9,'Value Meal Combos','Complete value meal combinations.','2026-10-05 12:09:33'),(10,'Small Plates & Starters','Crispy starters, appetizers and gourmet finger foods','2026-10-05 12:18:01'),(11,'Fresh Salads & Bowls','Nutritious sprout, peanut and Mediterranean salads','2026-10-05 12:18:01'),(12,'Tacos & Quesadillas','Mexican and Italian filled soft/crunchy tacos and grilled tortillas','2026-10-05 12:18:01'),(13,'Traditional Khichdi Bowls','Authentic Marwadi, Masala and Lahsooni Palak comfort khichdi','2026-10-05 12:18:01');
/*!40000 ALTER TABLE `catagories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_items`
--

DROP TABLE IF EXISTS `food_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) DEFAULT NULL,
  `catagory_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `catagory_id` (`catagory_id`),
  CONSTRAINT `food_items_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `food_items_ibfk_2` FOREIGN KEY (`catagory_id`) REFERENCES `catagories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_items`
--

LOCK TABLES `food_items` WRITE;
/*!40000 ALTER TABLE `food_items` DISABLE KEYS */;
INSERT INTO `food_items` VALUES (112,1,1,'Four Cheese Fries (250g)','Crispy potato fries smothered in four artisanal melted cheeses.',320.00,'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(113,1,1,'Parmesan Truffle Fries (250g)','Golden fries tossed with aromatic truffle oil and aged parmesan.',360.00,'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(114,1,1,'Peri Peri Fries (250g)','Spicy and zesty seasoned crinkle-cut fries.',260.00,'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(115,1,1,'Masala Potato Wedges (250g)','Hand-cut spiced potato wedges served with chipotle dip.',280.00,'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(116,1,1,'Nachos Grande (250g)','Crispy corn tortilla chips loaded with salsa, jalapenos, and queso.',350.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSW8rC6uLTFZ2irN-Nw25I8Z0eBE9v9eTRxCBX6kLDAwZQXmPI9nKV305cp&s=10',1,'2026-10-05 12:18:01'),(117,1,1,'Sauteed Mushroom (250g)','Fresh button mushrooms tossed with herbs and roasted garlic butter.',380.00,'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(118,1,1,'Sauteed Vegetables (250g)','Garden fresh broccoli, zucchini, bell peppers and baby corn.',340.00,'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(119,1,1,'Mezze Platter (250g)','Hummus, falafel, pita bread, olives, and fresh dips.',520.00,'https://images.unsplash.com/photo-1541544741938-0af808871cc0?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(120,1,1,'Cheese Fondue (250g)','Signature Swiss warm cheese dip served with toasted bread cubes.',750.00,'https://images.unsplash.com/photo-1518047601542-4505c00f5022?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(121,1,6,'Cheese Herb Garlic Bread (250g)','Crusty loaf with garlic herb butter and golden melted cheese.',500.00,'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(122,1,4,'Choco Lava Cake','Decadent chocolate cake with a molten fudge center.',220.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRg5JvKa8IyQpXjhoDWjuoasDGFWpMY995Fub87OUrXRSnHuayHVNbVR6tC&s=10',1,'2026-10-05 12:18:01'),(123,1,4,'Nutella Walnut Brownie','Fudgy brownie swirled with warm Nutella and roasted walnuts.',240.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6Aq1q9qx4o0FSbKXEdEpqSk-qKFuJJu98gyDJR6wrKi64hlXQFz3oAbPf&s=10',1,'2026-10-05 12:18:01'),(124,1,4,'New York Cheesecake','Classic dense and creamy cheesecake slice with berry compote.',280.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT8v6NQiFxidRbHclYdtq2eMLHHHl7ZO3qsbWRR9o7pgLIkwhkkTbD0W4k_&s=10',1,'2026-10-05 12:18:01'),(125,1,5,'Signature Cold Brew Coffee','Steeped for 18 hours for a smooth, bold coffee flavor.',210.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRX7Def1B17wbzjck_PrHpzCDSZsOTGuiIrU56QxId-MITBcmzF8uKaTCE&s=10',1,'2026-10-05 12:18:01'),(126,1,5,'Belgian Chocolate Milkshake','Rich blended shake made with premium Belgian chocolate.',250.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTIHIz91mveCtEXSXDp9TWHfr2iKRTwVvzxkmnOp-9eqj75KAjabwyDzjA&s=10',1,'2026-10-05 12:18:01'),(127,2,11,'Peanut Salad','Chatpata Peanut with Green Chutney',290.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRV602zNhHIwRZ3c_7ErAKHDV1ZQ8TX9livBZX-oqsYNT4JFZdeioEu9_c&s=10',1,'2026-10-05 12:18:01'),(128,2,11,'Sprout Beans Salad','Sprouted Beans Gives Tangy Taste',310.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTX8kl2UReSXaLX0T3mhmd5pZ6lauayXp0I_ejW_KgJCMSj-GJw8t1MCUI&s=10',1,'2026-10-05 12:18:01'),(129,2,11,'Peach Mozzarella Salad','Peach, Mozzarella Cubes, Lettuce & Bread Crumbs',350.00,'https://static01.nyt.com/images/2024/07/18/multimedia/HLM-Peach-Cucumber-and-Mozzarella-Salad-With-Gochujang-Dressin-czkp/HLM-Peach-Cucumber-and-Mozzarella-Salad-With-Gochujang-Dressin-czkp-mediumSquareAt3X.jpg',1,'2026-10-05 12:18:01'),(130,2,11,'Caesar Salad','Tofu, Lettuce, Cherry Tomato, Onions, Jalapeno, Olives, Parmesan Cheese & Cucumber',350.00,'https://playswellwithbutter.com/wp-content/uploads/2024/06/Charred-Sweet-Corn-Caesar-Salad-12.jpg',1,'2026-10-05 12:18:01'),(131,2,10,'Dry Manchurian','Fried Vegetable Balls Tossed in Savoury Sauce',260.00,'https://temptingtreat.com/wp-content/uploads/2025/02/manchurian-with-product-p1-2.jpg',1,'2026-10-05 12:18:01'),(132,2,10,'Spring Roll','Rolled Appetizer with Shredded Carrot & Other Vegetables Served with Dipping Sauce',280.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjiKKpEUDMBgXOB4ZMjz_DogOtesapCCfPSXpzY1K5p7uy2aTFgIewwZQi&s=10',1,'2026-10-05 12:18:01'),(133,2,10,'Cigar Rolls','Fried Chinese Rolls - Made by Rolling a Paneer Mixture Into Cigar-Shaped Rolls',290.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRsEqKXiC3gnBNrM1-i496w5qypFf_5evCpjHYw6qeuOd7V8FeY58MfuyQJ&s=10',1,'2026-10-05 12:18:01'),(134,2,10,'Water Chestnuts Plum Sauce','Fried Water Chestnut Balls Tossed in Savoury Sauce',300.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSPYO3cVXCPfFDY-4NfdZ5JslmklifTgGWRjoCauruiWntr9r7Ws4QnhNL5&s=10',1,'2026-10-05 12:18:01'),(135,2,10,'Paneer Manchurian Dry','Manchurian Dumpling in Hot & Sour Sauce',300.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQkD08EqmYCNeiEywaEAa375ca89I1X_PhgRXY8M_VSRcMoIE_cBPCedxfv&s=10',1,'2026-10-05 12:18:01'),(136,2,10,'Spinach Cheese Balls','Small Crispy Balls Filled with Spinach & Grated Cheese',310.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRZRj3pLLITqAet8Bd_CkBCp96KkgCPye0OLP30KehuwxUqcs2OVaOAnWY&s=10',1,'2026-10-05 12:18:01'),(137,2,10,'Paneer Chilly Dry','Cubes of Fried Crispy Paneer Tossed in Spicy Sauce',320.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRb6P_fHG_--fIvi4gx5hx19dezwqEUWRCo9BvoH_t4WmsmB47VUzFofyvl&s=10',1,'2026-10-05 12:18:01'),(138,2,10,'Pesto Cottage Cheese','Paneer Served with Pesto',320.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRJhUUwMl-Nsj6kSuU4c5Nd0r_hZKP396jk45ljLlRVzWI8McdWfiF-thM&s=10',1,'2026-10-05 12:18:01'),(139,2,10,'Jalapeno Cheese Pouch','Pocket Pouch Filled with Jalapeno & Cheese',320.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQpabkUcbr3ss3IxP3vmu2uZm4OLU0C1skrqiEmkduHL1uR-fH6RsHGRxQ&s=10',1,'2026-10-05 12:18:01'),(140,2,10,'Crispy Chilli Mushroom','Spicy Fried Mushroom',320.00,'https://static.toiimg.com/thumb/84753069.cms?imgsize=263439&width=800&height=800',1,'2026-10-05 12:18:01'),(141,2,13,'Masala Khichdi','Blend of Indian Spices',290.00,'https://www.funfoodfrolic.com/wp-content/uploads/2022/06/Masala-Khichid-Blog.jpg',1,'2026-10-05 12:18:01'),(142,2,13,'Rajasthani Khichdi','Bringing the Marwadi Taste in Khichdi',290.00,'https://maayeka.com/wp-content/uploads/2019/01/3-bajra-khichdi-recipe-1-1.jpg',1,'2026-10-05 12:18:01'),(143,2,13,'Lahsooni Palak Khichdi','A Healthy Indian Dish made with Rice & Palak Puree',310.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTol98JKCBPXcT2zGfbTxgkJ1WajYmCh64MtCkong_PAKCYSgilqvMuwImv&s=10',1,'2026-10-05 12:18:01'),(144,2,12,'Mexican Quesadilla','Tortilla Stuffed with Beans, Onion, Bell Peppers, Olives, Jalapeno, Lettuce, Shredded Cheese, Nachos Chips & Salsa',280.00,'https://images.unsplash.com/photo-1618040996337-56904b7850b9?w=600&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(145,2,12,'Italian Quesadilla','Tortilla Stuffed with Capsicum, Red Cabbage, Onions, Corn, Bell Pepper, Spring Onion, Lettuce, Mayonnaise & Shredded Cheese',310.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ98d5VqaItsqFfNxiKpywLRyRQeRAaFjz-eYmBABDS7AeJ1seRidgcHuoh&s=10',1,'2026-10-05 12:18:01'),(146,2,12,'Exotic Veg Quesadilla','Tortilla Stuffed with Exotic Vegetables',320.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSXNG1b_9awBX9XltDtxxKFaXjvgFVSdZKGsFwlQ6-8z-RsOd8FSUpJoyNx&s=10',1,'2026-10-05 12:18:01'),(147,2,12,'Beans & Cheese Tacos','Baked Beans Filling with Cheese (Soft or Crunchy)',290.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTAlxUQLoKMIztgfGr7HE9RLKHFxNhss91jdIEfO0n1xnofzEx6ttWZOg5a&s=10',1,'2026-10-05 12:18:01'),(148,2,12,'Supreme Tacos','Mexican Masala, Cheese, Lettuce & Salsa Dip (Soft or Crunchy)',320.00,'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(149,2,12,'Cottage Cheese Tacos','Capsicum, Onion, Corn, Paneer & Mozzarella (Soft or Crunchy)',350.00,'https://www.taylorfarms.com/wp-content/uploads/2026/03/High-Protein-Cottage-Cheese-Tacos-with-Southwest-Chicken-Avocado.webp',1,'2026-10-05 12:18:01'),(150,3,6,'Veg. Soup of the Day (180 ml)','Chef special freshly prepared wholesome vegetable soup served piping hot.',135.00,'https://www.indianhealthyrecipes.com/wp-content/uploads/2021/08/vegetable-soup.jpg',1,'2026-10-05 12:18:01'),(151,3,6,'Tomato Soup (180 ml)','Rich, tangy and creamy slow-simmered tomato soup with aromatic herbs.',90.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS64GosALQw10PPDP6qmhd2BqOElgIwFGfSY39m7ycgys2X1ltZQHa9cRU&s=10',1,'2026-10-05 12:18:01'),(152,3,6,'French Fries (150 gm)','Golden-fried crispy salted potato fries served with dip.',85.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ8fqP0TRgmlQrhIopl3-5eexoDxpGHuQDco7BHp5NZ175kB5aiyuOF3Uo7&s=10',1,'2026-10-05 12:18:01'),(153,3,6,'Garlic / Jain Bread (4 Pcs.)','Freshly baked sliced baguette brushed with aromatic garlic herb butter (Jain preparation available).',85.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCmQTnQS9dV9xptT_BAzFLf6qye5WXw8etwozl_fUAoKGAWU9HAI89oxTC&s=10',1,'2026-10-05 12:18:01'),(154,3,6,'Garlic / Jain Bread With Cheese (4 Pcs.)','Crispy garlic bread loaded with golden bubbly melted 100% mozzarella cheese.',105.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTy6agNErxjbKqam1KjZD-4-gpGwu00nTdWZa9gauwyN9ndvsJQIJsfI9wS&s=10',1,'2026-10-05 12:18:01'),(155,3,6,'Garlic / Jain Bread Supreme (4 Pcs.)','Spicy supreme garlic bread topped with melted cheese, chopped chillies and choice of toppings.',125.00,'https://5.imimg.com/data5/VI/KL/GLADMIN-31704146/jain-supreme-bread-250x250.png',1,'2026-10-05 12:18:01'),(156,3,6,'Garlic Bread Supreme - Sweet Corn, Chilly & Cheese (4 Pcs.)','Combo-1 Supreme: Sweet golden corn kernels, green chillies & melted mozzarella on garlic toast.',125.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR7kuO0SiDMdy5NBg0tu2yilz1vS9HVDV22PU7rBemtjVg5YAB3nbHLf0_Y&s=10',1,'2026-10-05 12:18:01'),(157,3,6,'Garlic Bread Supreme - Capsicum, Tomato, Chilly & Cheese (4 Pcs.)','Combo-2 Supreme: Fresh capsicum, juicy diced tomatoes, spicy chillies & gooey melted cheese.',125.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSvEW3Ar0fTUA4hc9TlTK9oYpJ9PAtGJZYpDGqg8hHFNpYTZ89wlW-DSiyF&s=10',1,'2026-10-05 12:18:01'),(158,3,6,'Garlic Bread Supreme - Onion, Chilli & Cheese (4 Pcs.)','Combo-3 Supreme: Crunchy sliced red onions, pungent green chillies & bubbly mozzarella cheese.',125.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQcjj2fAELcx0753QtSJZYTJLEEoWPcpqq3atENHXeUm6mH6zrSCxr0RVMr&s=10',1,'2026-10-05 12:18:01'),(159,3,6,'Garlic Bread Supreme - Mushroom, Chilly & Cheese (4 Pcs.)','Combo-4 Supreme: Earthy sauteed mushrooms, spicy chillies & melted cheese on crisp garlic baguette.',125.00,'https://cdn.uengage.io/uploads/247/image-1582732436.jpeg',1,'2026-10-05 12:18:01'),(160,3,6,'Garlic Bread Supreme - Pineapple, Chilly & Cheese (4 Pcs.)','Combo-5 Supreme: Tangy sweet pineapple chunks paired with fiery chillies & rich melted mozzarella.',125.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR540KmXAjz-ZjgvbziBholvluRDfQ2LgMoeTsn-636vnyCPq5ySk9yx_sw&s=10',1,'2026-10-05 12:18:01'),(161,3,7,'Salad Bar Bowl (600 gm)','Signature unlimited Salad Bar bowl with choice of fresh vegetables, pasta salads, beans & gourmet dressings.',210.00,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(162,3,7,'Salad Bar Plate (1 kg)','Generous 1 kg grand Salad Bar plate filled with an assortment of cold pasta salads, fresh greens & condiments.',375.00,'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(163,3,9,'Double Trouble Meal Combo','01 Regular Pizza (from any combination) + 01 Salad Bowl (600 gm) + 02 Pcs Garlic Bread Cheese (50 gm) + 02 Glasses Coke (280 ml each).',435.00,'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(164,3,9,'Triple Treat Meal Combo','01 Medium Pizza (from any combination) + 01 Salad Bowl (600 gm) + 03 Pcs Garlic Bread Cheese (75 gm) + 01 Coke Pitcher (1 Ltr).',695.00,'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(165,3,9,'Fun For Four Meal Combo','01 Large Pizza (from any combination) + 01 Salad Bowl (600 gm) + 04 Pcs Garlic Bread Cheese (100 gm) + 01 Coke Pitcher (1 Ltr).',875.00,'https://images.unsplash.com/photo-1534308983496-4fabb1a015ee?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(166,3,9,'Family Pack Meal Combo','01 Large Pizza + 01 Regular Pizza (any combinations) + 02 Salad Bowls (600 gm each) + 06 Pcs Garlic Bread Cheese (150 gm) + 02 Coke Pitchers (1 Ltr each).',1350.00,'https://images.unsplash.com/photo-1593560708920-61dd98c46a4e?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(167,3,8,'Red Indian Pizza (Regular)','Spicy Italian-style crust topped with Hungarian pepper, onion, capsicum, paneer & melted cheese.',205.00,'https://cdn.uengage.io/uploads/5/image-753633-1715839655.jpeg',1,'2026-10-05 12:18:01'),(168,3,8,'Red Indian Pizza (Medium)','Spicy medium pizza topped with Hungarian pepper, onion, capsicum, paneer & melted cheese.',425.00,'https://cdn.uengage.io/uploads/5/image-753633-1715839655.jpeg',1,'2026-10-05 12:18:01'),(169,3,8,'Red Indian Pizza (Large)','Spicy large feast pizza loaded with Hungarian pepper, onion, capsicum, soft paneer & mozzarella cheese.',595.00,'https://cdn.uengage.io/uploads/5/image-753633-1715839655.jpeg',1,'2026-10-05 12:18:01'),(170,3,8,'Party Lovers Pizza (Regular)','Classic favorite topped with crunchy capsicum, sweet corn, juicy tomatoes & gooey cheese.',205.00,'https://cdn.uengage.io/uploads/5/image-764994-1781243416.jpeg',1,'2026-10-05 12:18:01'),(171,3,8,'Party Lovers Pizza (Medium)','Generous medium pizza topped with crunchy capsicum, golden sweet corn, ripe tomatoes & melted cheese.',425.00,'https://cdn.uengage.io/uploads/5/image-764994-1781243416.jpeg',1,'2026-10-05 12:18:01'),(172,3,8,'Party Lovers Pizza (Large)','Large celebration pizza with abundant sweet corn, crisp capsicum, diced tomatoes & mozzarella cheese.',595.00,'https://cdn.uengage.io/uploads/5/image-934878-1782313228.jpeg',1,'2026-10-05 12:18:01'),(173,3,8,'Spanish Passion Pizza (Regular)','Spanish-inspired blend of juicy sweet corn, black sliced olives, spicy pickled jalapenos & cheese.',205.00,'https://b.zmtcdn.com/data/dish_photos/47c/051af7436641c6a2e3bab6f9a814047c.jpeg',1,'2026-10-05 12:18:01'),(174,3,8,'Spanish Passion Pizza (Medium)','Medium pizza layered with sweet corn, Spanish black olives, zesty jalapenos & 100% mozzarella cheese.',425.00,'https://b.zmtcdn.com/data/dish_photos/47c/051af7436641c6a2e3bab6f9a814047c.jpeg',1,'2026-10-05 12:18:01'),(175,3,8,'Spanish Passion Pizza (Large)','Large pizza with full toppings of sweet corn, rich black olives, spicy jalapenos & bubbly mozzarella cheese.',595.00,'https://b.zmtcdn.com/data/dish_photos/47c/051af7436641c6a2e3bab6f9a814047c.jpeg',1,'2026-10-05 12:18:01'),(176,3,8,'Hawaiian Ecstasy Pizza (Regular)','Spicy & sweet fusion of soft paneer cubes, golden juicy pineapple, capsicum, green chilly & cheese.',205.00,'https://cdn.uengage.io/uploads/5/image-641558-1781243416.jpeg',1,'2026-10-05 12:18:01'),(177,3,8,'Hawaiian Ecstasy Pizza (Medium)','Medium tropical pizza loaded with fresh paneer, succulent pineapple chunks, crisp capsicum, chillies & cheese.',425.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT0bNzzvZhRPMGOL1Zsa2GaX-_jpf62XB7Jc6ftRxjuNQ&s',1,'2026-10-05 12:18:01'),(178,3,8,'Hawaiian Ecstasy Pizza (Large)','Large supreme pizza loaded with spiced paneer, sweet pineapple chunks, capsicum, green chillies & melted mozzarella.',595.00,'https://cdn.uengage.io/uploads/5/image-641558-1781243416.jpeg',1,'2026-10-05 12:18:01'),(179,3,8,'Veggie Delight Pizza (Regular)','Deluxe vegetable pizza loaded with sliced onions, crisp capsicum, button mushrooms, ripe tomatoes, black olives & cheese.',205.00,'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(180,3,8,'Veggie Delight Pizza (Medium)','Medium hearty pizza with onions, capsicum, mushrooms, diced tomatoes, black olives & mozzarella cheese.',425.00,'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01'),(181,3,8,'Veggie Delight Pizza (Large)','Large feast pizza loaded with sliced onions, capsicum, button mushrooms, ripe tomatoes, olives & mozzarella cheese.',595.00,'https://images.unsplash.com/photo-1571407970349-bc81e7e96d47?w=500&auto=format&fit=crop&q=80',1,'2026-10-05 12:18:01');
/*!40000 ALTER TABLE `food_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_reviews`
--

DROP TABLE IF EXISTS `food_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `food_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `food_id` (`food_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `food_reviews_ibfk_1` FOREIGN KEY (`food_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `food_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `food_reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_reviews`
--

LOCK TABLES `food_reviews` WRITE;
/*!40000 ALTER TABLE `food_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `food_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `food_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `food_item_id` (`food_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`food_item_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cgst` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sgst` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `partner_earnings` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Preparing','Out for Delivery','Delivered','Cancelled') DEFAULT 'Pending',
  `delivery_address` text NOT NULL,
  `phone` varchar(15) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash on Delivery',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,8,750.00,18.75,18.75,0.00,787.50,10.00,75.00,712.50,'Pending','101 Green Valley, Satellite, Ahmedabad','9876543210','Cash on Delivery','2026-10-05 12:09:33','2026-10-05 12:09:33'),(2,3,8,210.00,5.25,5.25,0.00,220.50,10.00,21.00,199.50,'Delivered','101 Green Valley, Satellite, Ahmedabad','9876543210','Cash on Delivery','2026-10-05 12:09:33','2026-10-05 12:09:33'),(3,3,8,695.00,17.38,17.38,0.00,729.76,10.00,69.50,660.26,'Delivered','101 Green Valley, Satellite, Ahmedabad','9876543210','Cash on Delivery','2026-10-05 12:13:08','2026-10-05 12:13:28');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurant_reviews`
--

DROP TABLE IF EXISTS `restaurant_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurant_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `restaurant_id` (`restaurant_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `restaurant_reviews_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `restaurant_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `restaurant_reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurant_reviews`
--

LOCK TABLES `restaurant_reviews` WRITE;
/*!40000 ALTER TABLE `restaurant_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `restaurant_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `restaurants`
--

DROP TABLE IF EXISTS `restaurants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `restaurant_name` varchar(150) NOT NULL,
  `owner_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `license_number` varchar(100) NOT NULL,
  `license_issue_date` date NOT NULL,
  `license_expiry_date` date NOT NULL,
  `license_document` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `status` enum('Pending','Approved','Rejected','Suspended') NOT NULL DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `license_number` (`license_number`),
  KEY `owner_id` (`owner_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `restaurants_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `restaurants`
--

LOCK TABLES `restaurants` WRITE;
/*!40000 ALTER TABLE `restaurants` DISABLE KEYS */;
INSERT INTO `restaurants` VALUES (1,2,'KOMO BY KAFFA','Rajesh Sharma','KbK@gmail.com','9825012345','Sindhu Bhavan Road, Bodakdev','Ahmedabad','Gujarat','FSSAI-GUJ-2024-8891','2024-01-10','2028-01-10','license_kbk.pdf','','Artisan gourmet kitchen serving European delights, fondues, platters, and specialty coffee.',10.00,'Approved',NULL,1,'2026-10-05 14:09:33','2026-10-05 12:09:33','2026-10-05 12:09:33'),(2,3,'Camilo Coffee','Mayank Dave','CC@gmail.com','9825067890','Vastrapur Lake Road','Ahmedabad','Gujarat','FSSAI-GUJ-2024-3412','2024-02-15','2028-02-15','license_cc.pdf','','Cozy cafe offering specialty brews, pan-Asian appetizers, and fresh continental bakery items.',10.00,'Approved',NULL,1,'2026-10-05 14:09:33','2026-10-05 12:09:33','2026-10-05 12:09:33'),(3,4,'Sam\'s Pizza','Ketul Patel','sam@gmail.com','9825099887','CG Road, Navrangpura','Ahmedabad','Gujarat','FSSAI-GUJ-2024-5544','2024-03-01','2028-03-01','license_sam.pdf','','Pioneer of unlimited pizza buffet, loaded garlic breads, fresh salad bars, and value meal combos.',10.00,'Approved',NULL,1,'2026-10-05 14:09:33','2026-10-05 12:09:33','2026-10-05 12:09:33');
/*!40000 ALTER TABLE `restaurants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `role` enum('customer','admin','restaurant_owner') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Admin','admin@1234.com','$2y$10$oeorulr6LCOsD6HFXV4eXeDv13PDnd9J/fu6Q4dYuofNnFmDuCq4q','9876543210','Admin Office, Central Street','admin','2026-10-05 12:09:33'),(2,'Rajesh Sharma','KbK@gmail.com','$2y$10$pwCjwnzUP0xmpksQEJuUTeaS6ZM68quS2Gbg.o/D3ggtxrNht1LXW','9825012345','Sindhu Bhavan Road, Bodakdev, Ahmedabad','restaurant_owner','2026-10-05 12:09:33'),(3,'Mayank Dave','CC@gmail.com','$2y$10$lss97Lou5Qv5SGqZCf0iq.zrxFdeWP5yvfpkLFFdUvPrUtfZU2lLG','9825067890','Vastrapur Lake Road, Ahmedabad','restaurant_owner','2026-10-05 12:09:33'),(4,'Ketul Patel','sam@gmail.com','$2y$10$mA5m4gWDye1IGd9PUSerIuGo/z0z/2jFipIpbwMkdHsqFko0GNn6i','9825099887','CG Road, Navrangpura, Ahmedabad','restaurant_owner','2026-10-05 12:09:33'),(8,'Dev Darji','darjidev2504@gmail.com','$2y$10$6evHX2iv8MC42KNv6O17Bur90BhVcd9RUUVznAB5j7EYII6gHPhFa','9876543210','101 Green Valley, Satellite, Ahmedabad','customer','2026-10-05 12:09:33');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-13 15:16:43
